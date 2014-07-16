<?php
/**
 * User: dongww
 * Date: 14-7-15
 * Time: 上午10:01
 */

namespace Dongww\Db\Doctrine\Dbal\Core;


class TreeManager extends Manager
{
    public function addChildNode(Bean $bean, $parentId = null)
    {
        $bean->parent_id = $parentId;
        $qb              = $this->getConnection()->createQueryBuilder();
        $qb
            ->select('max(sort)')
            ->from($this->tableName, $this->aliases());

        if ($parentId) {
            $qb
                ->where('parent_id = ?')
                ->setParameter(0, $parentId);

            $parentBean = $bean->parent;

        } else {
            $qb
                ->where('parent_id is null');

            $parentBean = null;
        }

        $maxSort     = $this->getConnection()->fetchColumn($qb->getSQL(), $qb->getParameters());
        $bean->sort  = $maxSort + 1;
        $bean->path  = $this->getChildPath($parentBean);
        $bean->level = $this->getChildLevel($parentBean);

        return $this->store($bean);
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
