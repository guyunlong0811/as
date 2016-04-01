----------------更新历史------------------------------
--日期：2014-08-05
--作者：xipxop
--修改：创建脚本

function star_gold_refresh(gold_times, diamond_times)

	-- 根据洗练的次数给予百分比加成比例的返回
	-- 理论上，洗练次数越多，返回的百分比就越高
	-- 水晶洗练的百分比一般要高于金币洗练的百分比
	-- 任何洗练的比例不会低于5%

	-- 定义属性1和2的百分比下限和上限
	local PERCENT_GOLD_INF = 5
	local PERCENT_GOLD_SUF = 25

    -- 初始化随机数
    math.randomseed(tostring(os.time()):reverse():sub(1, 6))
    local percent_1, percent_2

	if gold_times < 10 then
       percent_1 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),10)
       percent_2 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),10)
    elseif gold_times >= 10 and gold_times < 50 then
       percent_1 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),15)
       percent_2 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),15)
    elseif gold_times >= 50 and gold_times < 100 then
       percent_1 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),18)
       percent_2 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),18)
	elseif gold_times >= 100 and gold_times < 300 then
       percent_1 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),20)
       percent_2 = math.min(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),20)
	else
	   percent_1 = math.max(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),10)
       percent_2 = math.max(math.random(PERCENT_GOLD_INF,PERCENT_GOLD_SUF ),10)
	end

    return percent_1,percent_2

end


function star_diamond_refresh(gold_times, diamond_times)

	-- 根据洗练的次数给予百分比加成比例的返回
	-- 理论上，洗练次数越多，返回的百分比就越高
	-- 水晶洗练的百分比一般要高于金币洗练的百分比
	-- 任何洗练的比例不会低于5%

	-- 定义属性1和2的百分比下限和上限
	local PERCENT_DIAMOND_INF = 10
	local PERCENT_DIAMOND_SUF = 25

    -- 初始化随机数
    math.randomseed(tostring(os.time()):reverse():sub(1, 6))
    local percent_1, percent_2

	if diamond_times < 10 then
       percent_1 = math.min(math.random(PERCENT_DIAMOND_INF, PERCENT_DIAMOND_SUF),15)
       percent_2 = math.min(math.random(PERCENT_DIAMOND_INF, PERCENT_DIAMOND_SUF),15)
    elseif diamond_times >= 10 and diamond_times < 30 then
       percent_1 = math.min(math.random(PERCENT_DIAMOND_INF, PERCENT_DIAMOND_SUF),20)
       percent_2 = math.min(math.random(PERCENT_DIAMOND_INF, PERCENT_DIAMOND_SUF),20)
	else
	   percent_1 = math.max(math.random(PERCENT_DIAMOND_INF, PERCENT_DIAMOND_SUF),15)
       percent_2 = math.max(math.random(PERCENT_DIAMOND_INF, PERCENT_DIAMOND_SUF),15)
	end

    return percent_1,percent_2

end

-- print(star_diamond_refresh(1, 1))