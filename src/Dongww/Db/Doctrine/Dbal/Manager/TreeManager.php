<?php
/**
 * User: dongww
 * Date: 14-7-15
 * Time: 上午10:01
 */

namespace Dongww\Db\Doctrine\Dbal\Manager;

use Doctrine\DBAL\Connection;

class TreeManager extends Manager
{
    const INSERT_PREVIOUS = 1;
    const INSERT_NEXT     = 2;

    protected $categories;

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
        return $this->insertNode($insertBean, $currentBean, self::INSERT_PREVIOUS);
    }

    public function addNextNode(Bean $insertBean, Bean $currentBean)
    {
        return $this->insertNode($insertBean, $currentBean, self::INSERT_NEXT);
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
    {//todo 可能有点问题，需要进一步测试
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

        return parent::remove($bean);
    }

    protected function getChildPath(Bean $bean = null)
    {
        $path = null;

        if ($bean) {
            $basePath = $bean->path ?: '/';
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

    protected function reloadCategory()
    {
        $qb = $this->getSelectQueryBuilder()
            ->select($this->allFields())
            ->orderBy('sort');

        return $this->categories = $this->getConnection()->fetchAll($qb->getSQL());
    }

    protected function hasChildren($id)
    {
        foreach ($this->categories as $row) {
            if ($row['parent_id'] == $id) {
                return true;
            }
        }

        return false;
    }

    public function getTreeView($parent = 0)
    {
        if (!$this->categories) {
            $this->reloadCategory();
        }

        $result = '<ul>';
        if ($this->categories) {
            foreach ($this->categories as $row) {
                if ($row['parent_id'] == $parent) {
                    $result .= '<li class="jstree-open" id="category_' .
                        $this->getTableName() . '_' . $row['id'] . '">' . $row['title'];
                    if ($this->hasChildren($row['id'])) {
                        $result .= $this->getTreeView($row['id']);
                    }

                    $result .= "</li>";
                }
            }
        }

        $result .= "</ul>";

        return $result;
    }

    /**
     * 获得排序后的列表
     *
     * @param  int          $pid
     * @return array|string
     */
    protected function getSorted($pid = null)
    {
        if (!$this->categories) {
            $this->reloadCategory();
        }
        $result = [];
        if ($this->categories) {
            foreach ($this->categories as $row) {
                if ($row['parent_id'] == $pid) {
                    $result[] = $row;
                    if ($this->hasChildren($row['id'])) {
                        $result = array_merge($result, (array) $this->getSorted($row['id']));
                    }
                }
            }
        }

        return $result;
    }

    public function getCateMap($pre = '--')
    {
        $data   = $this->getSorted();
        $return = [];
        foreach ($data as $d) {
            $str              = str_repeat($pre, $d['level'] - 1);
            $return[$d['id']] = $str . $d['title'];
        }

        return $return;
    }

    /**
     * 获取子节点的id集合
     *
     * @param  int   $pid
     * @param  bool  $topLevel
     * @param  bool  $includeSelf
     * @return array
     */
    public function getChildrenIds($pid = 0, $topLevel = false, $includeSelf = true)
    {
        $id  = (int) $pid;
        $ids = [];

        if ($id < 1) {
            $qb = $this->getSelectQueryBuilder()->select('id');

            $data = $this->getConnection()->fetchAll($qb->getSQL());
            foreach ($data as $d) {
                $ids[] = (int) $d['id'];
            }

            return $ids;
        }

        if ($includeSelf) {
            $ids[] = $id;
        }

        if ($topLevel) {
            $qb = $this->getSelectQueryBuilder()
                ->select('id')
                ->where('parent_id = :pid')
                ->setParameter('pid', $id);

            $data = $this->getConnection()->fetchAll($qb->getSQL(), $qb->getParameters());
            foreach ($data as $d) {
                $ids[] = (int) $d['id'];
            }
        } else {
            $qb = $this->getSelectQueryBuilder()
                ->select('id')
                ->where('path like :path')
                ->setParameter('path', '%/' . $id . '/%');

            $data = $this->getConnection()->fetchAll($qb->getSQL(), $qb->getParameters());
            foreach ($data as $d) {
                $ids[] = (int) $d['id'];
            }
        }

        return $ids;
    }

    /**
     * 获得子节点的Bean集合
     *
     * @param  int    $pid
     * @param  bool   $topLevel
     * @param  bool   $includeSelf
     * @return Bean[]
     */
    public function getChildren($pid = 0, $topLevel = false, $includeSelf = true)
    {
        $ids   = $this->getChildrenIds($pid, $topLevel, $includeSelf);
        $beans = [];

        $qb = $this->getSelectQueryBuilder()
            ->select('*')
            ->where('id in (?)');

        $data = $this->getConnection()->fetchAll(
            $qb->getSQL(),
            array($ids),
            array(Connection::PARAM_INT_ARRAY)
        );

        foreach ($data as $d) {
            $beans[] = $this->createBean($d);
        }

        return $beans;
    }
    //todo 新增编辑移动节点时，以数组形式将路径信息保存到数据库，以便读取。 格式：[['id'=>1, 'title'=>'节点名称'], [...]]
}
