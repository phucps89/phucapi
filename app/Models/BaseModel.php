<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/27/2018
 * Time: 9:15 AM
 */

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    public static function getTableName()
    {
        return (new static)->getTable();
    }

    public static function getPriKeyName()
    {
        return (new static)->getKeyName();
    }

    public static function getColumnName($column)
    {
        return self::getTableName() . '.' . $column;
    }

    public function getCreatedAtAttribute($attr)
    {
        return  Carbon::parse($attr)->format(FORMAT_DATE_TIME);
    }

    public function getUpdatedAtAttribute($attr)
    {
        return  Carbon::parse($attr)->format(FORMAT_DATE_TIME);
    }

    protected function getDateTimeTypeValue($attr){
        if($attr instanceof \DateTime){
            return $attr->format(FORMAT_DATE_TIME);
        }
        return Carbon::parse($attr)->format(FORMAT_DATE_TIME);
    }
}