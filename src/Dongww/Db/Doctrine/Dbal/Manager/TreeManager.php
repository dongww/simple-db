<?php
/**
 * User: dongww
 * Date: 14-7-15
 * Time: 上午10:01
 */

namespace Dongww\Db\Doctrine\Dbal\Manager;

class TreeManager extends Manager
{
    const INSERT_PREVIOUS = 1;
    const INSERT_NEXT     = 2;

    public function addChildNode(Bean $bean, Bean $parentBean = null)
    {
        $qb = $this->getSelectQueryBuilder()
            ->select('max(sort)');

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
        $this->insertNode($insertBean, $currentBean, self::INSERT_PREVIOUS);
    }

    public function addNextNode(Bean $insertBean, Bean $currentBean)
    {
        $this->insertNode($insertBean, $currentBean, self::INSERT_NEXT);
    }

    public function insertNode(Bean $insertBean, Bean $currentBean, $position = self::INSERT_NEXT)
    {
        $parentId   = $currentBean->parent_id;
        $sort       = $currentBean->sort;
        $parentBean = $currentBean->parent;

        $qb = $this->getUpdateQueryBuilder()
            ->set('sort', 'sort + 1');

        if ($position == self::INSERT_PREVIOUS) {
            $qb->where('sort >= :sort');
            $insertBean->sort = $sort;
        } else {
            $qb->where('sort > :sort');
            $insertBean->sort = $sort + 1;
        }

        $qb->setParameter('sort', $sort);

        if ($parentId) {
            $qb
                ->andWhere('parent_id = :pid')
                ->setParameter('pid', $parentId);
        } else {
            $qb->andWhere('parent_id is null');
        }

        $this->getConnection()->executeUpdate($qb->getSQL(), $qb->getParameters());

        $insertBean->parent_id = $parentId;
        $insertBean->path      = $this->getChildPath($parentBean);
        $insertBean->level     = $this->getChildLevel($parentBean);

        return $this->store($insertBean);
    }

    public function move(Bean $bean, Bean $newParentBean = null, $newSort = 1)
    {
        $oldParentId = $bean->parent_id;
        $oldSort     = $bean->sort;

        //原来的同级排序进行压缩
        $qb = $this->getUpdateQueryBuilder()
            ->set('sort', 'sort - 1')
            ->where('sort > :sort')
            ->setParameter('sort', $oldSort);

        if ($oldParentId) {
            $qb
                ->andWhere('parent_id = :pid')
                ->setParameter('pid', $oldParentId);
        } else {
            $qb->andWhere('parent_id is null');
        }

        $this->getConnection()->executeUpdate($qb->getSQL(), $qb->getParameters());

        //新的统计排序给出空挡
        $qb = $this->getUpdateQueryBuilder()
            ->set('sort', 'sort + 1')
            ->where('sort >= :sort')
            ->setParameter('sort', $newSort);

        if ($newParentBean) {
            $qb
                ->andWhere('parent_id = :pid')
                ->setParameter('pid', $newParentBean->id);
        } else {
            $qb->andWhere('parent_id is null');
        }

        $this->getConnection()->executeUpdate($qb->getSQL(), $qb->getParameters());

        $replacePathFrom  = $bean->path . $bean->id . '/';
        $replaceLevelFrom = $bean->level;

        //更新移动节点的path、sort、level
        $bean->parent_id = $newParentBean ? $newParentBean->id : null;
        $bean->sort      = $newSort;
        $bean->path      = $this->getChildPath($newParentBean);
        $bean->level     = $this->getChildLevel($newParentBean);

        //更新所有子节点的路径
        if ($this->store($bean)) { //echo 1;exit;
            $replacePathTo  = $bean->path . $bean->id . '/';
            $replaceLevelTo = $bean->level;

            $qb = $this->getUpdateQueryBuilder()
                ->set('path', 'REPLACE(path, :replace_path_from, :replace_path_to)')
                ->set('level', 'level + ( :replace_level_to - :replace_level_from )')
                ->where('path like :like')
                ->setParameter('replace_path_from', $replacePathFrom)
                ->setParameter('replace_path_to', $replacePathTo)
                ->setParameter('replace_level_to', $replaceLevelTo)
                ->setParameter('replace_level_from', $replaceLevelFrom)
                ->setParameter('like', $replacePathFrom . '%');

            $this->getConnection()->executeUpdate($qb->getSQL(), $qb->getParameters());

            return true;
        }

        return false;
    }

    public function remove(Bean $bean)
    {
        $qb = $this->getSelectQueryBuilder()
            ->select('id')
            ->where('parent_id = :pid')
            ->setParameter('pid', $bean->id);

        $data = $this->getConnection()->fetchAll($qb->getSQL(), $qb->getParameters());

        foreach ($data as $d) {
            $childBean = $this->get($d['id']);
            $this->move($childBean, null, 1);
        }

        $qb = $this->getUpdateQueryBuilder()
            ->set('sort', 'sort - 1')
            ->where('sort > :sort')
            ->setParameter('sort', $bean->sort);

        if ($bean->parent_id) {
            $qb
                ->andWhere('parent_id = :pid')
                ->setParameter('pid', $bean->parent_id);
        } else {
            $qb->andWhere('parent_id is null');
        }

        $this->getConnection()->executeUpdate($qb->getSQL(), $qb->getParameters());

        parent::remove($bean);
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
