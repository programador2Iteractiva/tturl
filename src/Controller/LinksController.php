<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Security;
use Hashids\Hashids;

/**
 * Links Controller
 *
 * @property \App\Model\Table\LinksTable $Links
 *
 * @method \App\Model\Entity\Link[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class LinksController extends AppController
{
    /**
     * @var Hashids
     */
    private $hashIds;

    public function initialize(): void
    {
        parent::initialize();

        $this->hashIds = new Hashids(Security::getSalt());

        $this->Authentication->allowUnauthenticated(['view', 'updateUrlShort', 'getVisitCount']);
    }

    public function index(): void
    {
        $id = $this->hashIds->decode($this->request->getQuery('id'))[0] ?? 0;

        $this->set('response', $this->Links->find()->where(['id' => $id]));
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * View method
     *
     * @param string|null $id Link id.
     *
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $link = $this->Links->get($this->hashIds->decode($id));

        $link->set('visit_count', $link->visit_count + 1);

        $this->Links->save($link);

        $this->redirect($link->url);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->getRequest()->allowMethod(['post']);

        $data = $this->getRequest()->getData();

        $uri = $this->getRequest()->getUri();

        $existingUrl = $this->Links->findByUrl($data['url'])->first();

        $shorten_id = null;

        if ( ! $existingUrl) {
            $link = $this->Links->newEmptyEntity();
            $data = [
                'url' => $this->request->getData('url')
            ];
            $link = $this->Links->patchEntity($link, $data);

            $link->set('user_id', $this->Authentication->getIdentity()->getIdentifier());

            /*$shorten = $this->hashIds->encode($existingUrl->id ?? $shorten_id);
            $link->set('short', $shorten);*/

            $link = $this->Links->save($link);

            $shorten_id = $link->id;

            $link->set('shorten_id', $shorten_id);

            $this->Links->save($link);
            if (isset($link) && $link->getErrors()) {
                $response = ['error' => $link->getError('url')['url'] ?? $link->getError('url')['_empty']];
            }
        
        
        }else{ //Ya existe

    
            $shorten = $this->hashIds->encode($existingUrl->id ?? $shorten_id);

            $response = [
                'url'      => "{$uri->getScheme()}://{$uri->getHost()}/{$shorten}",
                'shorten'  => $shorten,
                'url_long' => $this->request->getData('url')
            ];


            //Actualiza Short
            $data=$this->Links->get($existingUrl->id);
            $dato=['short_tt'=>$shorten];
            $user = $this->Links->patchEntity($data, $dato);
            $this->Links->save($user);
            //Actualiza Short
        }
        

        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    public function viewHash()
    {
        $this->getRequest()->allowMethod(['post']);
        $data = $this->getRequest()->getData();

        $existingUrl = $this->Links->get($data['id']);

        $bit=$existingUrl;
        if ( $existingUrl) {
            $bit=$this->hashIds->encode($data['id']);

            //Actualiza HASH si no lo tiene
            $save='No update';
            if(!$data['short_tt']){

                $dato=['short_tt'=>$bit];
                $user = $this->Links->patchEntity($existingUrl, $dato);

                if ($this->Links->save($user)) {
                    $save='update';
                }else{
                    $save='No';
                    //$save=$user->getErrors();
                }
            }
        }

        $response = [
            //'url'      => "{$uri->getScheme()}://{$uri->getHost()}/{$shorten}",
            'data'  => $existingUrl,
            'shorten'  => $bit,
            'fail'  => $save,
            //'url_long' => $this->request->getData('url')
        ];

        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    public function updateLinks()
    {

        $this->getRequest()->allowMethod(['get']);

        $response=null;
        $urls = $this->Links->find('All', ['conditions'=>['short_tt is null', 'date(created) >=' =>'2024-01-01', 'url NOT LIKE'=>'%eva.seguro%']])->toArray();
        foreach ($urls as $url){

            $data=$this->Links->get($url['id']);
            $bit=$this->hashIds->encode($data['id']);
            $dato=['short_tt'=>$bit];

            $user = $this->Links->patchEntity($data, $dato);
            if ($this->Links->save($user)) {}

        }

        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', 'response');
    }
    
    
    public function updateUrlShort()
    {
        $this->getRequest()->allowMethod(['post']);
    
        $short_tt = $this->request->getData('short_tt');
        $newUrl   = $this->request->getData('url');
    
        $response = [
            'success' => false,
            'message' => 'short_tt no encontrado',
            'short_tt' => $short_tt,
            'url' => null
        ];
    
        if (!empty($short_tt) && !empty($newUrl)) {
    
            $existingLink = $this->Links->find()
                ->where(['url' => $newUrl])
                ->first();
    
            if ($existingLink) {
                $response = [
                    'success' => false,
                    'message' => 'La URL ya existe asociada al short_tt: ' . $existingLink->short_tt,
                    'short_tt' => $existingLink->short_tt,
                    'url' => $existingLink->url
                ];
            } else {
                
                $link = $this->Links->find()
                    ->where(['short_tt' => $short_tt])
                    ->first();
    
                if ($link) {
                    $link->url = $newUrl;
    
                    if ($this->Links->save($link)) {
                        $response = [
                            'success' => true,
                            'message' => 'URL actualizada correctamente',
                            'short_tt' => $short_tt,
                            'url' => $newUrl
                        ];
                    } else {
                        $response['message'] = 'No se pudo guardar el cambio';
                    }
                }
            }
        } else {
            $response['message'] = 'short_tt y url son obligatorios';
        }
    
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', 'response');
    }
    
    
    public function getVisitCount()
    {
        $this->getRequest()->allowMethod(['post']);
    
        $short_tt = $this->request->getData('short_tt');
    
        $response = [
            'success' => false,
            'message' => 'short_tt es obligatorio',
            'short_tt' => $short_tt,
            'visit_count' => null
        ];
    
        if (!empty($short_tt)) {
            $link = $this->Links->find()
                ->select(['short_tt', 'visit_count'])
                ->where(['short_tt' => $short_tt])
                ->first();
    
            if ($link) {
                $response = [
                    'success' => true,
                    'message' => 'Contador obtenido correctamente',
                    'short_tt' => $link->short_tt,
                    'visit_count' => $link->visit_count
                ];
            } else {
                $response['message'] = 'short_tt no encontrado';
            }
        }
    
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    public function latest()
    {
        $this->request->allowMethod(['get']);

        $links = $this->Links->find()
            ->select(['url', 'short_tt'])
            ->order(['created' => 'DESC'])
            ->limit(10)
            ->all();

        $this->set('response', $links);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

}
