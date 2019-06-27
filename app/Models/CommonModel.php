<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class CommonModel extends Model
{
    /**
     * データ全件取得
     */
    public function getAll($offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {
        $result = DB::table($this->table);
    $result = $result->where('is_deleted', 0);
    if($offset != null) {
      $result = $result->offset($offset);
    }
    if($limit != null) {
      $result = $result->limit($limit);
    }
    if($orderParam != null) {
      $result = $result->orderBy($orderParam, $orderAsc);
    } else {
      $result = $result->orderBy('created_at', 'desc', 'updated_at', 'desc');
    }
    $result = $result->get();

        return $result;
    }

    /**
     * データ１件取得
     */
    public function getOne($id)
    {
        $result = DB::table($this->table)
          ->where($this->primary, '=', $id)
          ->where('is_deleted', 0)
          //->orderby($order, $asc)
          ->first();

        return $result;
    }

    /**
     * データ一覧取得
     */
    public function getList($params, $offset = null, $limit = null, $orderParam = null, $orderAsc = 'asc', $operators = null)
    {
        $result = DB::table($this->table);
        foreach($params as $key => $val) {
          if($operators == null) {
            $operator = '=';
          } else {
            $operator = $operators[$key];
          }
          $result = $result->where($key, $operator, $val);
        }
        $result = $result->where('is_deleted', 0);
    if($offset != null) {
      $result = $result->offset($offset);
    }
    if($limit != null) {
      $result = $result->limit($limit);
    }
    if($orderParam != null) {
      $result = $result->orderBy($orderParam, $orderAsc);
    } else {
      $result = $result->orderBy('created_at', 'desc', 'updated_at', 'desc');
    }
    $result = $result->get();
    
        return $result;
    }

    /**
     * データ更新
     */
    public function updateData($params, $data)
    {
        $update_data = $data;
        $update_data['updated_at'] = date('Y-m-d H:i:s');

        $result = DB::table($this->table);
        foreach($params as $key => $val) {
              $result = $result->where($key, $val);
        }
        $result = $result->update($update_data);

        return $result;
    }

    /**
     * データ論理削除
     */
    public function deleteData($id)
    {
        $delete_data = array(
          'is_deleted' => 1,
          'deleted_at' => date('Y-m-d H:i:s')
        );

        $result = DB::table($this->table);
        $result = $result->where('id', $id);
        $result = $result->update($delete_data);

        return $result;
    }
  
  /*
   * 件数を取得
   */
  public function totalCount() {
      
    $result = DB::table($this->table)
          ->where('is_deleted', 0)
          ->count();

        return $result;
    
  }
  
  /*
   * 次のauto incrementを取得
   */
  public function getNextId() {
    
    $result = DB::connection('mysql')
      ->select("SELECT auto_increment FROM information_schema.tables WHERE table_name = '".$this->table."'");
        
        return $result[0]->auto_increment;
        
  }
}
