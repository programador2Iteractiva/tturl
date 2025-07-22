<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Link Entity
 *
 * @property int $id
 * @property string $url
 * @property int|null $visit_count
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $user_id
 * @property string $short_tt
 * @property \App\Model\Entity\User $user
 */
class Link extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'url' => true,
        'visit_count' => true,
        'created' => true,
        'modified' => true,
        'user_id' => true,
        'short_tt' => true,
        'user' => true,
    ];
}
