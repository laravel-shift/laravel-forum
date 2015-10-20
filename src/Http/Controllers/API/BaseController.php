<?php

namespace Riari\Forum\Http\Controllers\API;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Routing\Controller;
use Riari\Forum\Forum;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @var mixed
     */
    protected $model;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $rules;

    /**
     * Create a new API controller instance.
     *
     * @param  Request  $request
     */
    public function __construct(Request $request)
    {
        $this->validate($request, [
            'with'      => 'array',
            'append'    => 'array',
            'orderBy'   => 'string',
            'orderDir'  => 'in:desc,asc'
        ]);

        $this->model = $this->model()->withRequestScopes($request);
    }

    /**
     * Return the model to use for this controller.
     *
     * @return string
     */
    abstract protected function model();

    /**
     * Return the translation file name to use for this controller.
     *
     * @return string
     */
    abstract protected function translationFile();

    /**
     * GET: Return an index of models.
     *
     * @param  Request  $request
     * @return JsonResponse|Response
     */
    public function index(Request $request)
    {
        return $this->response($this->model->paginate());
    }

    /**
     * GET: Return a model by ID.
     *
     * @param  int  $id
     * @param  Request  $request
     * @return JsonResponse|Response
     */
    public function fetch($id, Request $request)
    {
        $model = $this->model->find($id);

        if (is_null($model) || !$model->exists) {
            return $this->notFoundResponse();
        }

        return $this->response($model);
    }

    /**
     * PUT/PATCH: Update a model by ID.
     *
     * @param  int  $id
     * @return JsonResponse|Response
     */
    public function update($id)
    {
        $model = $this->model->find($id);

        if (is_null($model) || !$model->exists) {
            return $this->notFoundResponse();
        }

        $this->authorize('edit', $model);

        $response = $this->doUpdate($model, $this->request);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return $this->response($response, $this->trans('updated'));
    }

    /**
     * DELETE: Delete a model by ID.
     *
     * @param  Request  $request
     * @return JsonResponse|Response
     */
    public function destroy(Request $request)
    {
        $model = $this->model->find($request->input('id'));

        if (is_null($model) || !$model->exists) {
            return $this->notFoundResponse();
        }

        if ($request->has('force') && $request->input('force') == 1) {
            $model->forceDelete();
            return $this->response($model, $this->trans('perma_deleted'));
        } elseif (!$model->trashed()) {
            $model->delete();
            return $this->response($model, $this->trans('deleted'));
        }

        return $this->notFoundResponse();
    }

    /**
     * PATCH: Restore a model by ID.
     *
     * @param  Request  $request
     * @return JsonResponse|Response
     */
    public function restore(Request $request)
    {
        $model = $this->model->withTrashed()->find($request->input('id'));

        if (is_null($model) || !$model->exists) {
            return $this->notFoundResponse();
        }

        if ($model->trashed()) {
            $model->restore();
            return $this->response($model, $this->trans('restored'));
        }

        return $this->notFoundResponse();
    }

    /**
     * Update a given model's attributes.
     *
     * @param  Model  $model
     * @param  array  $attributes
     * @param  null|array  $authorize
     * @return JsonResponse|Response
     */
    protected function updateAttributes($model, array $attributes, $authorize = null)
    {
        if ($authorize) {
            list($ability, $authorizeModel) = $authorize;
            $this->authorize($ability, $authorizeModel);
        }

        $model->timestamps = false;
        $model->update($attributes);
        $model->timestamps = true;

        return $this->response($model, $this->trans('updated'));
    }

    /**
     * Carry out a bulk action.
     *
     * @param  Request  $request
     * @param  string  $action
     * @param  string  $transKey
     * @param  array  $input
     * @return JsonResponse|Response
     */
    protected function bulk(Request $request, $action, $transKey, array $input = [])
    {
        $this->validate($request, ['items' => 'required']);

        $threads = collect();
        foreach ($request->input('items') as $id) {
            $request->replace($input + compact('id'));
            $response = $this->{$action}($request);

            if (!$response->isNotFound()) {
                $threads->push($response->getOriginalContent());
            }
        }

        return $this->response($threads, $this->trans($transKey, $threads->count()));
    }

    /**
     * Create a generic response.
     *
     * @param  object  $data
     * @param  string  $message
     * @param  int  $code
     * @return JsonResponse|Response
     */
    protected function response($data, $message = "", $code = 200)
    {
        $message = empty($message) ? [] : compact('message');

        return (request()->ajax() || request()->wantsJson())
            ? new JsonResponse($message + compact('data'), $code)
            : new Response($data, $code);
    }

    /**
     * Create a 'not found' response.
     *
     * @return JsonResponse|Response
     */
    protected function notFoundResponse()
    {
        $content = ['error' => "Resource not found."];

        return (request()->ajax() || request()->wantsJson())
            ? new JsonResponse($content, 404)
            : new Response($content, 404);
    }

    /**
     * Create the response for when a request fails validation.
     *
     * @param  Request  $request
     * @param  array  $errors
     * @return JsonResponse|Response
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        $content = [
            'error'             => "The submitted data did not pass validation.",
            'validation_errors' => $errors
        ];

        return ($request->ajax() || $request->wantsJson())
            ? new JsonResponse($content, 422)
            : new Response($content, 422);
    }

    /**
     * Fetch a translated string.
     *
     * @param  string  $key
     * @param  int  $count
     * @return string
     */
    protected function trans($key, $count = 1)
    {
        return Forum::trans($this->translationFile(), $key, $count);
    }
}