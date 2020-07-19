<?php

namespace App\Plugins\cms\Controllers;

/**
 * @package    Plugin\cms\Controllers
 * @author     SygaTechnology Dev Team
 * @copyright  2019 SygaTechnology Foundation
 */
use Plugin\cms\Models\TaxonomyModel;
use Plugin\cms\Models\TermModel;
use Plugin\cms\Entities\Term;

/**
 * Class Terms
 *
 * @todo Terms Resource Controller
 *
 * @package App\Controller\Api
 * @return CodeIgniter\RESTful\ResourceController
 */

use App\Controllers\Api\ApiBaseController;
use \Plugin\cms\Args\TermArgs;

class Terms extends ApiBaseController
{
    public function index()
    {
        $params = \Config\Services::apiRequest();
        $taxonomyVar = $params->getParam('taxonomy');
        $limitVar = $params->getParam('limit');
        $offsetVar = $params->getParam('page');
        $orderVar = $params->getParam('order');
        $orderSensVar = $params->getParam('order_sens');
        $taxonomy = !is_null($taxonomyVar) ? $taxonomyVar : '*';
        $limit = !is_null($limitVar) ? (int) $limitVar : 10;
        $offset = !is_null($offsetVar) ? (int) $offsetVar : 1;
        $order = !is_null($orderVar) ? $orderVar : '';
        $orderSens = !is_null($orderSensVar) ? $orderSensVar : '';
        $termModel = new TermModel();
        $termModel
                ->setLimit($limit, $offset)
                ->setOrder($order, $orderSens)
                ->paginateResult();
        $result = $termModel->formatResult();
        return $this->respond($result, 200);
    }

    public function show($id = null)
    {
        $termModel = new TermModel();
        $term = $termModel->find($id);
        if ($term) {
            return $this->respond($term->getResult(), 200);
        }
        return $this->respond((object) array(), 404);
    }

    public function create()
    {
        if ($this->currentUser->isAuthorized("edit_term")) {

            $termArgsObject = $this->setArgs();

            if(! $termArgsObject->isValidTerm()){
                return $this->respond($termArgsObject->errors(), 500);
            }

            // Term Args
            $termArgs = $termArgsObject->getArgs();

            // Term Meta Args
            $termMetaArgs = $termArgsObject->getMetaArgs();

            $term = new Term();

            unset($termArgs['term_id']);

            $termData = [
                'term_args' => $termArgs,
                'termmeta_args' => $termMetaArgs
            ];
            $term->fillArgs($termData);

            $termModel = new TermModel();

            if ($termModel->insert($term) === false) {
                return $this->respond([$termModel->errors()], 400);
            }
            return $this->respondCreated(['id' => $termModel->getInsertID()]);
        }
        return $this->failForbidden("Create term capability required");
    }

    public function update($id = null)
    {
        if ($this->currentUser->isAuthorized("edit_term")) {

            $termArgsObject = $this->setArgs($id);

            if(! $termArgsObject->isValidTerm()){
                return $this->respond($termArgsObject->errors(), 500);
            }

            // Term Args
            $termArgs = $termArgsObject->getArgs();

            // Term Meta Args
            $termMetaArgs = $termArgsObject->getMetaArgs();

            $term = new Term();

            $termArgs['term_id'] = $termArgsObject->getID();

            $termData = [
                'term_args' => $termArgs,
                'termmeta_args' => $termMetaArgs
            ];
            $term->fillArgs($termData);

            $termModel = new TermModel();

            if ($termModel->update($termArgsObject->getID(), $term) === false) {
                return $this->respond([$termModel->errors()], 400);
            }
            return $this->respond(['Term updated'], 200);
        }
        return $this->failForbidden("Update term capability required");
    }

    private function setArgs($id = null){
        $request = \Config\Services::apiRequest();
        $termArgsObject = new TermArgs();
        $termArgsObject->fill($request, $id);
        return $termArgsObject;
    }

    public function delete($id = null)
    {
        if ($this->currentUser->isAuthorized("delete_term")) {
            if (!is_null($id) && is_numeric($id)) {
                $id = (int) $id;
                $termModel = new TermModel();
                $term = new Term($id);
                if($term->isNull()) return $this->failNotFound();
                $termModel->deleteTermRelationships($term->getField( 'term_taxonomy_id' ));
                $termModel->deleteTermTaxonomy($id);
                $termModel->deleteTermMeta($id);
                $termModel->delete($id);
                return $this->respondDeleted(['id' => $id]);
            }
            return $this->respond(['Term ID requierd'], 500);
        }
        return $this->failForbidden("Delete term capability required");
    }
}
