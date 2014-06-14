<?php
/**
 * User: dongww
 * Date: 14-5-6
 * Time: 下午4:03
 */

namespace Dongww\Db\RedBean;

abstract class TreeCategoryManagerAbstract extends ManagerAbstract
{
    protected static $parentId;
    protected $category;
    protected $name;

    protected $fields = [
        [
            'name'    => 'title',
            'type'    => 'string',
            'options' => [
                'unique' => true,
            ]
        ],
        [
            'name' => 'sort',
            'type' => 'integer',
        ],
        [
            'name' => 'path',
            'type' => 'string',
        ],
        [
            'name' => 'level',
            'type' => 'integer',
        ],
    ];
    protected $many2one;

    public function __construct()
    {
        parent::__construct();

        static::$parentId = static::$tableName . '_id';

        $this->many2one = [
            static::$tableName,
        ];

//        $this->reloadCategory();
    }

    protected function reloadCategory()
    {
        $category   = $this->findAll(' order by sort ');
        $parentName = static::$parentId;

        $this->category = [];

        foreach ($category as $c) {
            $this->category[] = [
                'id'        => $c->id,
                'title'     => $c->title,
                'parent_id' => $c->$parentName,
                'path'      => $c->path,
                'level'     => $c->level,
            ];
        }
    }

    /**
     * 判断是否有子分类
     *
     * @param $id
     * @return bool
     */
    protected function hasChildren($id)
    {
        foreach ($this->category as $row) {
            if ($row['parent_id'] == $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取树形分类html视图，可指定根节点
     *
     * @param int $parent
     * @return string
     */
    public function getTreeView($parent = 0)
    {
        if (!$this->category) {
            $this->reloadCategory();
        }
        $result = '<ul>';
        if ($this->category) {
            foreach ($this->category as $row) {
                if ($row['parent_id'] == $parent) {
                    $result .= '<li class="jstree-open" id="category_' .
                        static::$tableName . '_' . $row['id'] . '">' . $row['title'];
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

    public function getJson()
    {

    }

    /**
     * 在选择的分类下添加子分类，如果未选择父分类，则添加到根
     *
     * @param int $parentId
     * @param $title
     * @return bool
     */
    public function addChildNode($title, $parentId = 0)
    {
        $child        = $this->create();
        $child->title = $title;
        if ($parentId) {
            $parent  = $this->get($parentId);
            $maxSort = \R::getCell(
                "select max(sort) from " . static::$tableName . " where " . static::$parentId . " = :pid",
                [
                    'pid' => $parentId,
                ]
            );

            $child->sort = (int)$maxSort + 1;

            $parentName         = static::$tableName;
            $child->$parentName = $parent;

            $this->setPathLevel($child, $parent);
        } else {
            $count = $this->count();
            if ($count) {
                $maxSort     = \R::getCell(
                    sprintf("select max(sort) from %s where %s is null", static::$tableName, static::$parentId)
                );
                $child->sort = (int)$maxSort + 1;
            } else {
                $child->sort = 1;
            }
            $pid         = static::$parentId;
            $child->$pid = null;

            $this->setPathLevel($child);
        }

        if (\R::store($child)) {
            $this->reloadCategory();

            return true;
        }

        return false;
    }

    /**
     * 在选择的分类上方插入新分类
     *
     * @param $selectedId
     * @param $title
     * @return bool
     */
    public function addPreNode($title, $selectedId)
    {
        $selected = $this->get($selectedId);
        $sort     = $selected->sort;
        $p        = static::$parentId;
        $pid      = $selected->$p;
        if ($pid) {
            $whereP = sprintf('%s = %d', $p, $pid);
        } else {
            $whereP = sprintf(' %s is null ', $p);
        }
        \R::exec(
            sprintf(' update %s set sort = sort + 1 where %s and sort >= %d ', static::$tableName, $whereP, $sort)
        );
//        $nextAll = $this->findAll(sprintf(' where %s and sort >= %d '), $whereP, $sort);
//        foreach ($nextAll as $n) {
//            $n->sort = $n->sort + 1;
//            \R::store($n);
//        }
        $node        = $this->create();
        $node->title = $title;
        $node->sort  = $sort;
        $node->$p    = $pid;

        $this->setPathLevel($node, $pid ? $this->get($pid) : null);

        if (\R::store($node)) {
            $this->reloadCategory();

            return true;
        }

        return false;
    }

    /**
     * 在选择的分类下方插入新分类
     *
     * @param $selectedId
     * @param $title
     * @return bool
     */
    public function addNextNode($title, $selectedId)
    {
        $selected = $this->get($selectedId);
        $sort     = $selected->sort;
        $p        = static::$parentId;
        $pid      = $selected->$p;
        if ($pid) {
            $whereP = sprintf('%s = %d', $p, $pid);
        } else {
            $whereP = sprintf(' %s is null ', $p);
        }
        \R::exec(
            sprintf(' update %s set sort = sort + 1 where %s and sort > %d ', static::$tableName, $whereP, $sort)
        );

        $node        = $this->create();
        $node->title = $title;
        $node->sort  = $sort + 1;
        $node->$p    = $pid;

        $this->setPathLevel($node, $pid ? $this->get($pid) : null);

        if (\R::store($node)) {
            $this->reloadCategory();

            return true;
        }

        return false;
    }

    /**
     * 重命名某一分类
     *
     * @param $nodeId
     * @param $title
     * @return bool
     */
    public function rename($title, $nodeId)
    {
        $node        = $this->get($nodeId);
        $node->title = $title;
        if (\R::store($node)) {
            $this->reloadCategory();

            return true;
        }

        return false;
    }

    /**
     * 移动一个分类
     *
     * @param $nodeId
     * @param $newPId
     * @param $newSort
     * @return bool
     */
    public function move($nodeId, $newPId, $newSort)
    {
        //todo 分类同名判断
        $node    = $this->get($nodeId);
        $p       = static::$parentId;
        $oldPId  = $node->$p;
        $oldSort = $node->sort;

        if ($oldPId) {
            $whereP = sprintf('%s = %d', $p, $oldPId);
        } else {
            $whereP = sprintf(' %s is null ', $p);
        }
        \R::exec(
            sprintf(' update %s set sort = sort - 1 where %s and sort > %d ', static::$tableName, $whereP, $oldSort)
        );

        if ($newPId) {
            $whereP = sprintf('%s = %d', $p, $newPId);
        } else {
            $whereP = sprintf(' %s is null ', $p);
        }
        \R::exec(
            sprintf(' update %s set sort = sort + 1 where %s and sort >= %d ', static::$tableName, $whereP, $newSort)
        );

        $oldPath  = $node->path . $node->id . '/';
        $oldLevel = $node->level;

        $node->$p   = $newPId;
        $node->sort = $newSort;

        $this->setPathLevel($node, $newPId ? $this->get($newPId) : null);

        if (\R::store($node)) {
            $newPath  = $node->path . $node->id . '/';
            $newLevel = $node->level;

            $sql = sprintf(
                'update %s set path = REPLACE(path, ?, ?), level = level + (%d) where path like ?',
                static::$tableName,
                $newLevel - $oldLevel
            );
            \R::exec($sql, [$oldPath, $newPath, $oldPath . '%']);

            $this->reloadCategory();

            return true;
        }

        return false;
    }

    /**
     * 删除一个分类
     *
     * @param $nodeId
     * @param array $options
     * @return bool|void
     */
    public function delete($nodeId, $options = [])
    {
        $children = $this->findAll(sprintf(' %s = %d ', static::$parentId, $nodeId));

        foreach ($children as $c) {
            $this->move($c->id, null, 1);
        }

        $node = $this->get($nodeId);
        $p    = static::$parentId;
        $pid  = $node->$p;

        if ($pid) {
            $whereP = sprintf('%s = %d', $p, $pid);
        } else {
            $whereP = sprintf(' %s is null ', $p);
        }
        \R::exec(
            sprintf(' update %s set sort = sort - 1 where %s and sort > %d ', static::$tableName, $whereP, $node->sort)
        );

        parent::delete($nodeId);

        $this->reloadCategory();

        return true;
    }

    /**
     * 设置路径和层次
     *
     * @param $node
     * @param null $parent
     * @return mixed
     */
    public function setPathLevel($node, $parent = null)
    {
        if ($parent) {
            $pre         = $parent->path ? $parent->path : '/';
            $node->path  = $pre . $parent->id . '/';
            $node->level = $parent->level + 1;
        } else {
            $node->path  = null;
            $node->level = 1;
        }

        return $node;
    }

    /**
     * 获得排序后的列表
     *
     * @param int $pid
     * @return array|string
     */
    public function getSorted($pid = null)
    {
        if (!$this->category) {
            $this->reloadCategory();
        }
        $result = [];
        if ($this->category) {
            foreach ($this->category as $row) {
                if ($row['parent_id'] == $pid) {
                    $result[] = $row;
                    if ($this->hasChildren($row['id'])) {
                        $result = array_merge($result, (array)$this->getSorted($row['id']));
                    }
                }
            }
        }

        return $result;
    }

    public function getCateMap($pre = '')
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
     * @param $id
     * @param bool $topLevel 是否只获取下一级
     * @param bool $includeSelf 是否包含本身
     * @return array
     * @throws \Exception
     */
    public function getChildrenIds($id = 0, $topLevel = false, $includeSelf = true)
    {
        $id = (int)$id;

        if ($id < 1) {
            $ids = \R::getCol(
                sprintf('select id from %s', static::$tableName)
            );

            return $ids;
        }

        if ($topLevel) {
            $ids = \R::getCol(
                sprintf('select id from %s where %s = %d', static::$tableName, static::$parentId, $id)
            );
        } else {
            $ids = \R::getCol(
                sprintf('select id from %s where path like ?', static::$tableName),
                ['%/' . $id . '/%']
            );
        }

        if ($includeSelf) {
            $ids[] = $id;
        }

        return $ids;
    }
}