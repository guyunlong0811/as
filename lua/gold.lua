----------------更新历史------------------------------
--日期：2015-09-10
--作者：xipxop
--修改：创建脚本

--日期：2015-09-24
--作者：xipxop
--修改：提升点金的金币产出

--日期：2015-09-26
--作者：xipxop
--修改：修正单次点金和连续点金金额不一致的问题

-----------------------------------------------------

function buy_gold(team_level, total_buy_times, this_buy_times)

	-- 设定第n次点击需要的水晶
	local need_diamond = {}
	need_diamond[1] = 10
	need_diamond[2] = 20
	need_diamond[3] = 30
	need_diamond[4] = 40
	need_diamond[5] = 50
	need_diamond[6] = 60
	need_diamond[7] = 70
	need_diamond[8] = 80
	need_diamond[9] = 90
	need_diamond[10] = 100
	need_diamond[11] = 120
	need_diamond[12] = 150
	need_diamond[13] = 200

	-- 设定点金基础金额
	-- 根据战斗等级确定当前战队等级下的点金初始金额
	-- 根据当前已点金的次数，确定当前战队等级下，基于已点金过的次数，所确定的新的基数
	-- 根据本次点金的次数，确定在新基数下每次可获得的金币数量

	local basic_gold = 30000
	local curr_gold_base

	curr_gold_base = basic_gold + team_level * 200 

	local count	
	local gold = 0
	local consume_diamaond = 0

	for count = total_buy_times + 1 , total_buy_times + this_buy_times do
		gold = gold + curr_gold_base + (count - 1) * (team_level) * 200
		if count <=13 then
		   consume_diamaond = consume_diamaond + need_diamond[count]
		else
		   consume_diamaond = consume_diamaond + need_diamond[13]
		end
	end

    return consume_diamaond, gold
    
end

-- print(buy_gold(50,0,20))