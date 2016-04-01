<?php
namespace Home\Model;

use Think\Model;

class StaticModel extends BaseModel
{

    protected $connection = array(
        'db_type' => DB_STATIC_TYPE,
        'db_host' => DB_STATIC_HOST,
        'db_user' => DB_STATIC_USER,
        'db_pwd' => DB_STATIC_PWD,
        'db_port' => DB_STATIC_PORT,
        'db_name' => DB_STATIC_NAME,
        'db_charset' => DB_STATIC_CHARSET,
    );

    protected $autoCheckFields = false;
    private $mTable = array();
    private $mRow = array();

    private $mList = array(
        'daily_register' => array('order' => array('month' => 'asc', 'day' => 'asc',),),
        'event' => array('order' => array('group' => 'asc', 'start_date' => 'asc', 'start_time' => 'asc',),),
        'exchange' => array('order' => array('index' => 'asc', 'count' => 'asc',),),
        'partner_group' => array('table' => 'partner',),
        'partner_quest_group' => array('table' => 'partner_quest', 'order' => array('belong_partner' => 'ASC', 'index' => 'ASC',),),
        'time' => array('order' => array('type' => 'asc', 'value' => 'asc',),),
        'rank_bonus' => array('order' => array('rank_type' => 'asc', 'rank_start' => 'asc',),),
        'league_boss_group' => array('table' => 'league_boss',),
        'monster' => array('field' => array('index', 'name', 'loot_item', 'loot_0_count', 'loot_1_count', 'loot_2_count', 'loot_3_count', 'loot_4_count'),),
        'instance_info' => array('field' => array('index', 'need_vality', 'need_item', 'need_item_count', 'create_times', 'pre_instance', 'battle_1_monster', 'battle_2_monster', 'battle_3_monster', 'bonus_team_exp', 'bonus_partner_exp', 'bonus_gold', 'group', 'sweep_item', 'sweep_item_count', 'sweep_loot', 'first_bonus',),),
    );
    private $mModel;
    private $mIndex;
    private $mField;

    //获取单条配置
    public function access($model, $index = null, $field = null)
    {
        $this->mModel = $model;
        $this->mIndex = $index;
        $this->mField = $field;
//        dump($this->mModel.'.'.$this->mIndex.'.'.$this->mField);
        //全部属性
        if (empty($this->mIndex) && empty($this->mField)) {
            $config = $this->getAll();
        } else {
            //当是k-v表时，特殊
            if ($model == 'params') {
                $this->mField = 'value';
                $this->mIndex = strtoupper($this->mIndex);
            }

            //单条属性
            if (is_null($this->mField)) {
                $config = $this->getRow($index);
            } else {
                //单个属性
                if (!is_array($this->mField)) {
                    $config = $this->getAttr();
                } else {
                    $config = $this->getRow();
                    foreach ($this->mField as $value){
                        $config = $config[$value];
                    }
                }

            }

        }

        if ($config === false) {
            C('G_DEBUG_STATIC', $this->mModel . '.' . $this->mIndex . '.' . $this->mField);
            C('G_ERROR', 'config_error');
            exit;
        }
        return $config;
    }

    //获取单条配置
    private function getAttr()
    {

        //获取全部参数
        if (!$config = $this->getRow()) {
            return false;
        }
        if (isset($config[$this->mField])) {
            return $config[$this->mField];
        } else {
            C('G_DEBUG_STATIC', $this->mModel . '.' . $this->mIndex . '.' . $this->mField);
            C('G_ERROR', 'config_error');
            return false;
        }

    }

    //获取单条配置
    private function getRow()
    {

        //在一次进程中缓存表数据
        if(!isset($this->mRow[$this->mModel][$this->mIndex])){

            //缓存数据
            $this->mRow[$this->mModel][$this->mIndex] = S(C('APC_PREFIX') . 's_' . $this->mModel . ':' . $this->mIndex);

            //如果缓存中没有数据
            if (empty($this->mRow[$this->mModel][$this->mIndex])) {

                //获取整表数据
                if (!$config = $this->getAll()) {
                    return false;
                }

                //检查数据是否存在
                if (!isset($config[$this->mIndex])) {
                    C('G_DEBUG_STATIC', $this->mModel . '.' . $this->mIndex . '.' . $this->mField);
                    C('G_ERROR', 'config_error');
                    return false;
                }

                //存储缓存
                $this->mRow[$this->mModel][$this->mIndex] = $config[$this->mIndex];
                S(C('APC_PREFIX') . 's_' . $this->mModel . ':' . $this->mIndex, $this->mRow[$this->mModel][$this->mIndex]);

            }

        }

        //返回
        return $this->mRow[$this->mModel][$this->mIndex];

    }

    //获取全部配置
    private function getAll()
    {
        //在一次进程中缓存表数据
        if(!isset($this->mTable[$this->mModel])){
            $this->mTable[$this->mModel] = S(C('APC_PREFIX') . 's_' . $this->mModel);
        }

        //如果缓存中没有找到
        if (empty($this->mTable[$this->mModel])) {

            //从数据库获取数据
            $field = isset($this->mList[$this->mModel]['field']) ? $this->mList[$this->mModel]['field'] : true;
            $table = isset($this->mList[$this->mModel]['table']) ? 's_' . $this->mList[$this->mModel]['table'] : 's_' . $this->mModel;
            $where = isset($this->mList[$this->mModel]['where']) ? $this->mList[$this->mModel]['where'] : '1=1';
            $order = isset($this->mList[$this->mModel]['order']) ? $this->mList[$this->mModel]['order'] : array('index' => 'asc',);
            $all = $this->table($table)->field($field)->where($where)->order($order)->select();
            if (empty($all)) {
                C('G_DEBUG_STATIC', $this->mModel . '.' . $this->mIndex . '.' . $this->mField);
                C('G_ERROR', 'config_error');
                return false;
            }

            if (!empty($all)) {
                foreach ($all as $value) {
                    switch ($this->mModel) {
                        //特殊情况
                        case 'partner_group':
                            $this->mTable[$this->mModel][$value['group']][$value['index']] = $value;
                            break;
                        case 'partner_quest_group':
                            $this->mTable[$this->mModel][$value['belong_partner']][$value['index']] = $value;
                            break;
                        case 'equipment':
                            $this->mTable[$this->mModel][$value['group']][$value['index']] = $value;
                            break;
                        case 'time':
                            $this->mTable[$this->mModel][$value['type']][] = $value['value'];
                            break;
                        case 'shop_goods':
                            $this->mTable[$this->mModel][$value['goods_group']][] = $value;
                            break;
                        case 'daily_register':
                            $this->mTable[$this->mModel][$value['month']][$value['day']] = $value;
                            break;
                        case 'event':
                            $this->mTable[$this->mModel][$value['group']][$value['index']] = $value;
                            break;
                        case 'exchange':
                            $this->mTable[$this->mModel][$value['index']][$value['count']] = $value;
                            break;
                        case 'quest':
                            $this->mTable[$this->mModel][$value['type']][$value['index']] = $value;
                            break;
                        case 'rank_bonus':
                            $this->mTable[$this->mModel][$value['rank_type']][$value['index']] = $value;
                            break;
                        case 'league_boss_group':
                            $this->mTable[$this->mModel][$value['group']][$value['index']] = $value;
                            break;
                        case 'partner_awake':
                            $this->mTable[$this->mModel][$value['group']][$value['favour']] = $value;
                            break;
                        case 'enchant_attribute':
                            $this->mTable[$this->mModel][$value['group']][$value['index']] = $value;
                            break;
                        case 'enchant_skill':
                            $this->mTable[$this->mModel][$value['group']][$value['index']] = $value;
                            break;
                        //默认
                        default:
                            $this->mTable[$this->mModel][$value['index']] = $value;
                    }
                }
            }else{
                $this->mTable[$this->mModel] = array();
            }

            //存储缓存
            S(C('APC_PREFIX') . 's_' . $this->mModel, $this->mTable[$this->mModel]);

        }

        //返回
        return $this->mTable[$this->mModel];

    }

}