----------------更新历史------------------------------
--日期：2014-08-05
--作者：xipxop
--修改：创建脚本

--日期：2015-03-24
--作者：xipxop
--修改：调整战力算法

--日期：2015-07-30
--作者：xipxop
--修改：将命中、闪避、暴击、格挡等纳入战力计算；更新战力验证的算法和上限保护

-----------------------------------------------------

function cal_ability(partner_id, partner_class, CHAR_CURR_HP_LIMIT, CHAR_CURR_ATK, CHAR_CURR_MAGIC, CHAR_CURR_DEF, CHAR_CURR_RES, CHAR_CURR_ATK_SPEED, CHAR_CURR_HIT, CHAR_CURR_DODGE, CHAR_CURR_CRI, CHAR_CURR_BLOCK, CHAR_CURR_POISONNESS, CHAR_CURR_TOUGHNESS, CHAR_CURR_HEALTH_STEALTH, CHAR_CURR_SPELL_VAMP, CHAR_CURR_ARMOR_PEN, CHAR_CURR_MAGIC_PEN, CHAR_CURR_LUCK, CHAR_CURR_CURSE, CHAR_CURR_GOD)
-- 参数注释：
-- partner_id     			伙伴ID，用于精细化设定伙伴的战力算法
-- partner_class 			伙伴资质，用于最后界定初始值
-- CHAR_CURR_HP_LIMIT		当前生命上限
-- CHAR_CURR_ATK 			当前物理攻击
-- CHAR_CURR_MAGIC 			当前魔法攻击
-- CHAR_CURR_DEF  			当前物理防御
-- CHAR_CURR_RES			当前魔法防御
-- CHAR_CURR_ATK_SPEED		当前普攻攻击速度
-- CHAR_CURR_HIT 			当前命中
-- CHAR_CURR_DODGE  		当前闪避
-- CHAR_CURR_CRI 			当前暴击
-- CHAR_CURR_BLOCK 			当前格挡
-- CHAR_CURR_POISONNESS 	当前毒抗
-- CHAR_CURR_TOUGHNESS 		当前韧性
-- CHAR_CURR_HEALTH_STEALTH 当前生命偷取
-- CHAR_CURR_SPELL_VAMP		当前法术吸血
-- CHAR_CURR_ARMOR_PEN		当前物理穿透
-- CHAR_CURR_MAGIC_PEN 		当前法术穿透

----------------属性价值---------------------------
-- 战斗回合：5
-- 基准击打：10
-- 基准攻击：100
---------------------------------------------------
-- 生命上限：0.05
-- 物理攻击：0.3
-- 魔法攻击：0.3
-- 物理防御：1
-- 魔法防御：1
-- 攻击速度：暂不参与战力计算
-- 当前命中：0.5
-- 当前闪避：5
-- 当前暴击：1
-- 当前格挡：1
-- 当前毒抗：暂不参与战力计算
-- 当前韧性：暂不参与战力计算
-- 生命偷取：暂不参与战力计算
-- 法术吸血：暂不参与战力计算
-- 物理穿透：暂不参与战力计算
-- 法术穿透：暂不参与战力计算

	local power

	local temp_power

	local hp_point
	local atk_point
	local mag_point
	local def_point
	local res_point
	local hit_point
	local dod_point
	local cri_point
	local blk_point

	hp_point = CHAR_CURR_HP_LIMIT * 0.05

	atk_point = CHAR_CURR_ATK * 0.3
	mag_point = CHAR_CURR_MAGIC * 0.3

	def_point = CHAR_CURR_DEF * 1
	res_point = CHAR_CURR_RES * 1

	hit_point = CHAR_CURR_HIT * 0.5
	dod_point = CHAR_CURR_DODGE * 5

	cri_point = CHAR_CURR_CRI * 1
	blk_point = CHAR_CURR_BLOCK * 1

	temp_power = (hp_point + atk_point + mag_point + def_point + res_point + hit_point + dod_point + cri_point + blk_point) * 1

	power = math.max(temp_power, 2)

	return power

end


-- 战力验证函数
function verify_ability(partner_id, partner_class, partner_level, partner_favour)

	local MAX_ABILITY
	local power

	power = 50000

	MAX_ABILITY = math.min(power, 80000)

	return MAX_ABILITY

end

-- print(cal_ability(1001,0,1515,192,195,26,93,295,23,213,160,0,0,0,0,0,0,0))
-- 参数注释：
-- partner_id     			伙伴ID，用于精细化设定伙伴的战力算法
-- partner_class 			伙伴资质，用于最后界定初始值
-- CHAR_CURR_HP_LIMIT		当前生命上限
-- CHAR_CURR_ATK 			当前物理攻击
-- CHAR_CURR_MAGIC 			当前魔法攻击
-- CHAR_CURR_DEF  			当前物理防御
-- CHAR_CURR_RES			当前魔法防御
-- CHAR_CURR_ATK_SPEED		当前普攻攻击速度
-- CHAR_CURR_HIT 			当前命中
-- CHAR_CURR_DODGE  		当前闪避
-- CHAR_CURR_CRI 			当前暴击
-- CHAR_CURR_BLOCK 			当前格挡
-- CHAR_CURR_POISONNESS 	当前毒抗
-- CHAR_CURR_TOUGHNESS 		当前韧性
-- CHAR_CURR_HEALTH_STEALTH 当前生命偷取
-- CHAR_CURR_SPELL_VAMP		当前法术吸血
-- CHAR_CURR_ARMOR_PEN		当前物理穿透
-- CHAR_CURR_MAGIC_PEN 		当前法术穿透