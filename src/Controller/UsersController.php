<?php

declare(strict_types=1);

namespace App\Controller;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\Time;
use Cake\Utility\Security;
use Firebase\JWT\JWT;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['add', 'token']);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     *
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Links'],
        ]);

        $this->set('user', $user);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->request->allowMethod('post');

        $user = $this->Users->newEmptyEntity();

        $user = $this->Users->patchEntity($user, $this->request->getData());

        $user = $this->Users->saveOrFail($user);

        $this->set('user', $user);

        $this->viewBuilder()->setOption('serialize', 'user');
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    public function token(){
        $data = $this->request->getData();

        $username = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $this->Users->findByEmail($username)->first();

        $hashedPassword = $user->password ?? '';

        $isPasswordValid = (new DefaultPasswordHasher())->check($password, $hashedPassword);

        if (!$user || !$isPasswordValid) {
            throw new NotFoundException("User not found");
        }

        $payload = [
            'sub' => $user->id,
            'iat' => (new Time())->timestamp,
            'exp' => (new Time())->modify('+10 years')->timestamp
        ];

        $user->set('token', JWT::encode($payload, Security::getSalt()));

        $this->set('user', $user);

        $this->viewBuilder()->setOption('serialize', 'user');
    }
}
