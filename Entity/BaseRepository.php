<?php

namespace Mediapark\BaseRepositoryBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseRepository extends EntityRepository {

    protected $hydration = Query::HYDRATE_OBJECT;
    protected $qb;
    protected $filter = null;
    protected $enitity_name;

    function getEntityName() {
        return $this->enitity_name;
    }

    public function init($em, \Doctrine\ORM\Mapping\ClassMetadata $class) {
        $this->_entityName = $class->name;
        $this->_em = $em;
        $this->_class = $class;
    }

    public function setFilter(array $filter) {
        $this->filter = $filter;
    }

    public function addFilter($key, $value) {
        $this->filter[$key] = $value;
    }

    public function getFilter() {
        return $this->filter;
    }

    public function setHydration($hydration = Query::HYDRATE_OBJECT) {
        $this->hydration = $hydration;

        return $this;
    }

    protected function getHydration() {
        return $this->hydration;
    }

    protected function getResult() {
        return $this->getHydratedResult();
    }

    protected function getOneOrNullResult() {
        return $this->getHydratedResult(true);
    }

    protected function getHydratedResult($one_or_null = false) {
        if (is_null($this->qb)) {
            throw new NotFoundHttpException('QueryBuilder not initiated');
        }

        $q = $this->getHydratedQuery();

        $result = ($one_or_null) ? $q->getOneOrNullResult($this->getHydration()) : $q->getResult($this->getHydration());

        unset($this->qb);

        return $result;
    }

    protected function getHydratedQuery() {
        $q = $this->getQuery();

        if ($this->getHydration() == Query::HYDRATE_OBJECT) {
            $q->setHint(Query::HINT_REFRESH, 1);
        }

        return $q;
    }

    protected function createQB($alias, $index_by = null, $select = null) {
        $qb = null;

        if (false === is_null($index_by)) {
            $qb = $this->_em->createQueryBuilder()
                    ->select($select)
                    ->from($this->_entityName, $alias, $index_by);
        } else {
            $qb = $this->createQueryBuilder($alias);
        }

        $this->setQB($qb);

        return $this->qb;
    }

    protected function setQB($qb) {
        $this->qb = $qb;
    }

    protected function getQB() {
        return $this->qb;
    }

    protected function QB() {
        return $this->getQB();
    }

    protected function getQuery() {
        return $this->getQB()->getQuery();
    }

    protected function getExpr() {
        return $this->qb->expr();
    }

    protected function expr() {
        return $this->getExpr();
    }

    protected function filterExists() {
        return (false === is_null($this->getFilter())) ? true : false;
    }

    public function getFilterByKey($key, $alt = null) {
        return ($this->filterKeyExists($key)) ? $this->filter[$key] : $alt;
    }

    protected function filterKeyExists($key) {
        return (($this->filterExists() && isset($this->filter[$key]) && !is_array($this->filter[$key])) || (isset($this->filter[$key]) && is_array($this->filter[$key]) && !empty($this->filter[$key]))) ? true : false;
    }

    protected function getSQL() {
        return $this->getQuery()->getSQL();
    }

    protected function SQL() {
        return $this->getSQL();
    }

    protected function getDQL() {
        return $this->getQuery()->getDQL();
    }

    protected function DQL() {
        return $this->getDQL();
    }


    protected function addLimitFilter() {
        if ($this->filterKeyExists('limit')) {
            $firstResult = $this->getFilterByKey('first_result', 0);

            $this->qb()->setFirstResult($firstResult);
            $this->qb()->setMaxResults($this->getFilterByKey('limit'));
        }
    }

    protected function addOrderByFilter() {
        if ($this->filterKeyExists('orderBy')) {
            $orderHow = $this->getFilterByKey('orderHow', 'ASC');
            $orderBy = $this->getFilterByKey('orderBy');
            $orderBy = $this->splitOrderByClause($orderBy);
            $orderBy = $this->trimOrderBy($orderBy);
            $this->addOrderByArray($orderBy, $orderHow);
        }
    }

    protected function splitOrderByClause($orderBy) {
        $split = $orderBy;
        if (is_string($orderBy)) {
            $split = explode(',', $orderBy);
        }
        return $split;
    }

    protected function trimOrderBy($orderBy) {
        $r = array();
        foreach ($orderBy as $item) {
            $r[] = trim($item);
        }
        return $r;
    }

    protected function addOrderByArray($orderBy, $orderHow) {
        foreach ($orderBy as $item) {
            $this->addOrderBy($item, $orderHow);
        }
    }

    public function addOrderBy($orderBy, $orderHow) {
        $this->_addOrderBy($orderBy, $orderHow);
    }

    protected function _addOrderBy($orderBy, $orderHow) {
        $this->qb()->addOrderBy($orderBy, $orderHow);
    }

    public function addFiltersByArray($filter = array()) {
        foreach ($filters as $key => $value) {
            $this->addFilter($key, $value);
        }
    }

    public function where($value, $key = null, $param = null) {
        $this->qb()->andWhere($value);

        if (!is_null($key) && !is_null($param)) {
            $this->qb()->setParameter($key, $param);
        }
    }

    public function setArrayHydration() {
        $this->setHydration(Query::HYDRATE_ARRAY);
        return $this;
    }

    public function resetFilters() {
        $this->filter = null;
    }

    public function removeFilter($filter) {
        if ($this->filterKeyExists($filter)) {
            unset($this->filter[$filter]);
        }
    }

}
