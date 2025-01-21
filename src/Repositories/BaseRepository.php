<?php

namespace Modules\JangKeyte\src\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseRepository
 *
 * @package Modules\JangKeyte\src\Repositories
 */
class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all object
     * @return mixed
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * Get an object
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        return $this->model->where('id', $id)->firstOrNew();
    }

    /**
     * Create an object
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update an object
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update($request)
    {
        return $this->model->find($request->id)->update($request->all());
    }
    
    /**
     * Delete an object
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        //return $this->model->destroy($id); // xóa tất cả bản ghi có id này trả về số lượng bản ghi đã xóa
        return $this->model->find($id)->delete(); // xóa 1 bản ghi có id này trả về true false
    }

}