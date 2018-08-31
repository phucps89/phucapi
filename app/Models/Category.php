<?php

namespace App\Models;

/**
 * @property int $id
 * @property int $id_parent
 * @property string $name
 * @property string $slug
 * @property string $image
 * @property string $icon
 * @property string $created_at
 * @property string $updated_at
 */
class Category extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = ['id_parent', 'name', 'slug', 'image', 'icon', 'created_at', 'updated_at'];

}
