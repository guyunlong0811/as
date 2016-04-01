----------------更新历史------------------------------
--日期：2015-05-18
--作者：xipxop
--修改：创建脚本

--日期：2015-08-15
--作者：xipxop
--修改：更新当生命调低到10000后的奖励算法

--日期：2015-09-24
--作者：xipxop
--修改：提升猫之报恩的金币产出

--日期：2015-11-09
--作者：xipxop
--修改：提升猫之报恩的金币产出，修改算法结构

------------------------------------------------------

---猫之报恩
function cat_gold(team_level, max_combo, total_damage)

	local reward
	local combo_reward
	local damage_reward
	local level_reward

	-- 奖励基数
	local BASIC_REWARD = 20000

	-- 上限保护
	-- 对于超过2000 combo以上的combo，按2000计算
	-- 最高奖励30w
	local MAX_COMBO_LIMIT = 2000
	combo_reward = math.min(max_combo * 150, MAX_COMBO_LIMIT * 150 )

	-- 上限保护
	-- 猫男爵数据设定为10W生命，超出10W生命按上限计算
	-- 最高奖励30w
	local MAX_DAMAGE_LIMIT = 100000
	damage_reward = math.min(total_damage * 3, MAX_DAMAGE_LIMIT * 3)

	-- 上限保护
	-- 战队理论上等级上限为99，无论是否开放到99，超过99就按99计算
	-- 最高奖励 10w
	local TEAM_LEVEL_LIMIT = 99
	level_reward = math.min(team_level * 1000, TEAM_LEVEL_LIMIT * 1000)

	reward = BASIC_REWARD + combo_reward + damage_reward + level_reward

	return reward

end


---九命之喵
function cat_exp(team_level,max_combo,total_damage)

	local exp
	local reward

	exp = math.floor(max_combo * 10 + total_damage * 0.1)
	exp = math.min(exp, 20000)

    if team_level <= 40 then
	   reward = math.floor(exp/2000) + 900
	else
	   if exp <= 10000 then
	   	  reward = math.floor(exp/2000) + 900
	   else 
	      reward = math.floor(exp/2000) + 911
	   end
	end

	return reward

end

-- print(cat_gold(60,500,100000))
-- print(cat_exp(45,300,8000))