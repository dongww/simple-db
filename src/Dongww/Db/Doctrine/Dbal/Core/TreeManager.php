<?php
/**
 * User: dongww
 * Date: 14-7-15
 * Time: 上午10:01
 */

namespace Dongww\Db\Doctrine\Dbal\Core;


class TreeManager extends Manager
{
    public function addChildNode(Bean $bean, Bean $parentBean = null)
    {
        $qb = $this->getConnection()->createQueryBuilder();
        $qb
            ->select('max(sort)')
            ->from($this->tableName, $this->aliases());

        if ($parentBean != null) {
            $qb
                ->where('parent_id = ?')
                ->setParameter(0, $parentBean->id);
        } else {
            $qb
                ->where('parent_id is null');
        }

        $maxSort         = $this->getConnection()->fetchColumn($qb->getSQL(), $qb->getParameters());
        $bean->sort      = $maxSort + 1;
        $bean->path      = $this->getChildPath($parentBean);
        $bean->level     = $this->getChildLevel($parentBean);
        $bean->parent_id = $parentBean ? $parentBean->id : null;

        return $this->store($bean);
    }

    public function addPreNode(Bean $insertBean, Bean $currentBean)
    {
        $parentId   = $currentBean->parent_id;
        $sort       = $currentBean->sort;
        $parentBean = $currentBean->parent;

        $qb = $this->getConnection()->createQueryBuilder();
        $qb
            ->update($this->tableName, $this->aliases())
            ->set('sort', 'sort + 1')
            ->where('sort >= :sort')
            ->setParameter('sort', $sort);

        if ($parentId) {
            $qb
                ->andWhere('parent_id = :pid')
                ->setParameter('pid', $parentId);
        } else {
            $qb->andWhere('parent_id is null');
        }

        $this->getConnection()->executeUpdate($qb->getSQL(), $qb->getParameters());

//        var_dump($qb->getSQL());
//        var_dump($qb->getParameters());

        $insertBean->sort      = $sort;
        $insertBean->parent_id = $parentId;
        $insertBean->path      = $this->getChildPath($parentBean);
        $insertBean->level     = $this->getChildLevel($parentBean);

        return $this->store($insertBean);
    }

    protected function getChildPath(Bean $bean = null)
    {
        $path = null;

        if ($bean) {
            $basePath = $bean->path ? $bean->path : '/';
            $path     = $basePath . $bean->id . '/';
        } else {
            $path = null;
        }

        return $path;
    }

    protected function getChildLevel(Bean $bean = null)
    {
        $level = null;

        if ($bean) {
            $level = $bean->level + 1;
        } else {
            $level = 1;
        }

        return $level;
    }
}
