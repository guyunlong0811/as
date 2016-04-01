----------------更新历史------------------------------
--日期：2014-10-09
--作者：liyunfei
--修改：创建脚本
--联盟战相关脚本

--日期：2015-07-29
--作者：YuHua
--修改：修复返回值错误

--日期：2015-08-13
--作者：xipxop
--修改：修改每场战斗的掉落奖励的box编号

--日期：2015-11-19
--作者：xipxop
--修改：修改积分计算规则

--日期：2016-03-27
--作者：xipxop
--修改：修改公会战守护塔的生命上限

--日期：2016-03-31
--作者：xipxop
--修改：修改公会战参与奖励

-----------------------------------------------------

math.randomseed(tostring(os.time()):reverse():sub(1, 6))  

--联盟战结束后的积分结算

function league_battle_points(win_value,a_points,b_points,damage_rate) --传参：胜负平，自己积分，对方积分，累积伤害比
-- win_value = 0，失败
-- win_value = 1，胜利
-- win_value = 2，平局

	local points_me = tonumber(a_points)      -- 自己积分
	local points_you = tonumber(b_points)     -- 对方积分
	local value = tonumber(win_value)         -- 胜负参数
    local damage_me = tonumber(damage_rate)    -- 本次公会战造成的总伤害/对方公会建筑总生命上限，1 - 10000 = 0.01% - 100%

	-- 公会战积分由两部分组成：伤害分、结局分
	-- 伤害分：根据本次公会战中，我方对敌方建筑的总伤害/对方公会建筑总生命上限，进行计算，比例越高，积分越高
	-- 结局分：根据胜负结果给予额外积分奖励，
	-- 轮空时处理：轮空时没有参与公会战，不予积分奖励

	local damage_point
	-- 对于哪怕只打了一点伤害的，也给积分，说明也是战斗过了，所以不足1时，向上取整
	-- 伤害分设置安全保护，最高只会是100%伤害 = 100分
	damage_point = math.min(math.ceil(damage_me/100),100)     
	-- 根据期望胜率对伤害分进行调整，强打弱，如果双方伤害分相同，意味着弱者其实参与度更高，那么应该有积分额外奖励
    local param = 2/(1+20^((points_me-points_you)/400)) --计算修正参数
    -- 强者不会被扣分，所以修正参数不能小于1
    -- 弱者的加分也应该有限制，所有修正参数不能大于2
    param = math.min(math.max(param,1),2) 
    damage_point = damage_point * param
    -- 取整
    damage_point = math.floor(damage_point)

    local result_point
    -- 胜利 = 10分，平 = 0分， 失败 = -10分
    if value == 0 then 
       result_point = -10
    elseif value == 1 then
       result_point = 10
    else
       result_point = 0	
    end
    
    -- 轮空判断：己方胜利，且我方伤害率为0
    -- 轮空的一方不会获得伤害奖励，只会获得结局分（算胜）
	if value == 1 and damage_me == 0 then
	   return 10
	end
    
    -- 计算最终得分
    -- 最终得分 = 伤害分 + 结果分
	local final_point = damage_point + result_point

	return final_point

end

-- 联盟战匹配，返回匹配基准值
function league_battle_match(points,win_num,league_level, league_shop_level, league_food_level, league_attribute_level, league_boss_level) --传参：自己积分，最近5场战绩（0负，1胜，2平）

	local num = win_num 			--战绩
	local win_value = {} 		--战绩储存数组
	local count = string.len(num)
	local new_points = tonumber(points)
	local lx_index = 0 --连续数组编号
	local lx_num = 0 --胜负情况
	local lx_value = 1  --连续值
	local avx = {} --连续数组
	
	if count ~= 0 then	
		for i = 1,count do 				--战绩拆分
			win_value[i] = string.sub(num,i,i)
		
			local points_value = tonumber(win_value[i])
			
			if i > 1 and points_value == tonumber(win_value[i-1]) then  --记录战绩连续情况
				lx_value = lx_value + 1
			else
				lx_index = lx_index +1
				lx_value = 1
			end
			
			lx_num = points_value
			avx[lx_index] = {lx_num,lx_value}
		end
		
		for i = 1, #avx do
			local ls_num = avx[i][1] --胜负情况
			local ls_value = avx[i][2]  --连胜数

			if ls_num == 0 then  --将胜负转换成参数值
				ls_num = -1
			elseif ls_num == 1 then
				ls_num = 1
			elseif ls_num == 2 then
				ls_num = 0
			end
			new_points = math.ceil(new_points + (32 ^ (ls_num * ls_value * 0.3 ))) --计算公式
		end
		return new_points
	else
		return new_points
	end

end



--联盟战随机掉落技能使用次数脚本

function league_battle_reward_skill(instance_id, damage) --传参：目标据点

	local points = tonumber(damage)
	
	-- 根据伤害给参与奖励
	if points <= 200000 then
	   return 0, 2160002
	elseif points > 200000 and points <= 500000 then
	   return 0, 2160004
	else
	   return 0, 2160005
	end
    
    -- 参数1：技能类型，参数2：box
	-- return 0, 601
   
end

 --联盟排名奖励发放脚本
 
function league_battle_reward_mail(rank,win_value) --传参：排名，胜负
	local a = rank
	if a >= 201 then
		return 2310
	elseif a >= 101 and a < 200 then
		return 2311
	elseif a >= 51 and a < 100 then
		return 2312
	elseif a >= 21 and a < 50 then
		return 2313
	elseif a >= 11 and a < 20 then
		return 2314
	elseif a == 10 then
		return 2315
	elseif a == 9 then
		return 2316
	elseif a == 8 then
		return 2317
	elseif a == 7 then
		return 2318
	elseif a == 6 then
		return 2319
	elseif a == 5 then
		return 2320
	elseif a == 4 then
		return 2321
	elseif a == 3 then
		return 2322
	elseif a == 2 then
		return 2323
	elseif a == 1 then
		return 2324
	end
end


function league_battle_monster_hp(monster_id, league_level, league_food_level, league_shop_level, league_attribute_level, league_boss_level)

	local hp
	local league_basic_hp
	local league_level_add

	league_basic_hp = 5000000
	league_level_add = 0

	if monster_id == 1 then
       hp = 3000000 + ( league_level - 1) * league_level_add
    elseif monster_id == 2 then
       hp = 4000000 + ( league_level - 1) * league_level_add
    elseif monster_id == 3 then
       hp = 5000000 + ( league_level - 1) * league_level_add
    else
       hp = 4000000
    end
    
    -- 上限保护，不超过20亿
    hp = math.min(hp, 2000000000)
    
	return hp

end

-- print(league_battle_points(1,0,100,0))
