<?php

namespace App\Plugins\cms\Controllers;

/**
 * @package    Plugin\cms\Controllers
 * @author     SygaTechnology Dev Team
 * @copyright  2019 SygaTechnology Foundation
 */
use Plugin\cms\Models\TermModel;
use Plugin\cms\Models\PostModel;
use Plugin\cms\Entities\Post;

/**
 * Class Posts
 *
 * @todo Posts Resource Controller
 *
 * @package App\Controller\Api
 * @return CodeIgniter\RESTful\ResourceController
 */

use App\Controllers\Api\ApiBaseController;
use \Plugin\cms\Services\ObjectTypesService;
use \Plugin\cms\Args\PostArgs;

class Posts extends ApiBaseController
{
    public function index()
    {
        $params = \Config\Services::apiRequest();
        $statusVar = $params->getParam('status');
        $limitVar = $params->getParam('limit');
        $offsetVar = $params->getParam('page');
        $order = $params->getParam('order');
        $orderSens = $params->getParam('order_sens');
        $withDeletedVar = $params->getParam('with_deleted');
        $deletedOnlyVar = $params->getParam('deleted_only');
        $status = ($statusVar !== null) ? (($statusVar === 'all') ? null : $statusVar) : null;
        $limit = $limitVar !== null ? (int) $limitVar : 10;
        $offset = $offsetVar !== null ? (int) $offsetVar : 1;
        $withDeleted = $withDeletedVar != null ? true : false;
        $deletedOnly = $deletedOnlyVar != null ? true : false;
        $postModel = new PostModel();
        $postModel
                ->setStatus($status)
                ->setLimit($limit, $offset)
                ->setOrder($order, $orderSens)
                ->paginateResult();
        if($deletedOnly) $postModel->onlyDelete();
        if($withDeleted) $postModel->withDeleted();
        $result = $postModel->formatResult();
        $db = \Config\Database::connect();
        $data = [];
        foreach($result['data'] as $post){
            $post->taxonomies = $post->getTaxonomies();
            $data[] = $post;
        }
        $result['data'] = $data;
        return $this->respond($result, 200);
    }

    public function show($id = null)
    {
        $postModel = new PostModel();
        $post = $postModel->find($id);
        if ($post) {
            return $this->respond($post->getResult(), 200);
        }
        return $this->respond((object) array(), 404);
    }

    public function create()
    {
        if ($this->currentUser->isAuthorized("edit_post")) {

            $postArgsObject = $this->setArgs();

            if(! $postArgsObject->isValidPost()){
                return $this->respond($postArgsObject->errors(), 500);
            }

            // Post Args
            $postArgs = $postArgsObject->getArgs();

            // Post Meta Args
            $postMetaArgs = $postArgsObject->getMetaArgs();

            // Post Terms Args
            $postTermArgs = $postArgsObject->getTermArgs();

            $post = new Post();

            unset($postArgs['post_id']);

            $postData = [
                'post_args' => $postArgs,
                'postmeta_args' => $postMetaArgs,
                'post_term_args' => $postTermArgs
            ];
            $post->fillArgs($postData);

            $postModel = new PostModel();

            if ($postModel->insert($post) === false) {
                return $this->respond([$postModel->errors()], 400);
            }
            return $this->respondCreated(['id' => $postModel->getInsertID()]);
        }
        return $this->failForbidden("Create post capability required");
    }

    public function update($id = null)
    {
        if ($this->currentUser->isAuthorized("edit_post")) {

            $postArgsObject = $this->setArgs($id);

            if(! $postArgsObject->isValidPost()){
                return $this->respond($postArgsObject->errors(), 500);
            }

            // Post Args
            $postArgs = $postArgsObject->getArgs();

            // Post Meta Args
            $postMetaArgs = $postArgsObject->getMetaArgs();

            // Post Terms Args
            $postTermArgs = $postArgsObject->getTermArgs();

            $post = new Post();

            $postArgs['post_id'] = $postArgsObject->getID();

            $postData = [
                'post_args' => $postArgs,
                'postmeta_args' => $postMetaArgs,
                'post_term_args' => $postTermArgs
            ];
            $post->fillArgs($postData);

            $postModel = new PostModel();

            if ($postModel->update($postArgsObject->getID(), $post) === false) {
                return $this->respond([$postModel->errors()], 400);
            }
            return $this->respond(['Post updated'], 200);
        }
        return $this->failForbidden("Update post capability required");
    }

    private function setArgs($id = null){
        $request = \Config\Services::apiRequest();
        $postArgsObject = new PostArgs();
        $postArgsObject->fill($request, $id);
        return $postArgsObject;
    }

    public function delete($id = null)
    {
        if ($this->currentUser->isAuthorized("delete_post")) {
            if ($id !== null && is_numeric($id)) {
                $postModel = new PostModel();
                $post = new Post($id);
                if ($post->isNull()) return $this->failNotFound();
                $postModel->deletePostMeta($id);
                $postModel->deletePostTerms($id);
                $postModel->delete($id);
                return $this->respondDeleted(['id' => $id]);
            }
            if ($id !== null && is_string($id) && $id == 'purge') {
                $postModel = new PostModel();
                $posts = $postModel->getDeleted();
                $ids = [];
                foreach($posts as $post){
                    $ids[] = $post->post_id;
                }
                $postModel->purgePostMeta($ids);
                $postModel->purgePostTerms($ids);
                $postModel->purgePosts($ids);
                return $this->respondDeleted(['Deleted posts purged']);
            }
            return $this->respond(['Error on request'], 500);
        }
        return $this->failForbidden("Delete post capability required");
    }
}
