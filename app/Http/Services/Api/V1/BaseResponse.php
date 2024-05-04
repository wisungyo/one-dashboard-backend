<?php

namespace App\Http\Services\Api\V1;

class BaseResponse
{
    /**
     * Set response
     *
     * @param  bool  $status
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array|object  $data
     * @param  string  $note
     * @return array
     */
    protected function response($status = false, $message = 'Failed', $statusCode = 200, $data = [], $note = '')
    {
        return [
            'status' => $status,
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data,
            'note' => $note,
        ];
    }

    /**
     * Set response Success
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array|object  $data
     * @param  string  $note
     * @return array
     */
    protected function responseSuccess($message = 'Success', $statusCode = 200, $data = [], $note = '')
    {
        return $this->response(true, $message, $statusCode, $data, $note);
    }

    /**
     * Set response Error
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array|object  $data
     * @param  string  $note
     * @return array
     */
    protected function responseError($message = 'Failed', $statusCode = 500, $errors = 'Error', $note = '')
    {
        return $this->response(false, $message, $statusCode, ['errors' => $errors], $note);
    }

    /**
     * Filter Pipeline
     *
     * @param  object  $query
     * @param  array  $piplines
     * @param  object  $request
     * @return object
     */
    protected function filterPipeline($query, $piplines, $request)
    {
        return \Illuminate\Support\Facades\Pipeline::send($query)
            ->through($piplines)
            ->thenReturn();
    }

    /**
     * Filter List with Pagination
     *
     * @param  object  $obj
     * @param  array  $filter
     * @return object
     */
    protected function filterPagination($query, $piplines, $request)
    {
        // set default value
        $limit = $request->limit ?? 10;
        $request->sort_by = $request->sort_by ?? 'id';
        $request->sort = $request->sort ?? -1;

        // Filter process
        $data = $this->filterPipeline($query, $piplines, $request);

        return $data->paginate($limit)->appends($request->input());
    }
}
