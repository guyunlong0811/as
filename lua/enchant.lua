----------------更新历史------------------------------
--日期：2015-10-27
--作者：yuhua
--修改：呵呵

--日期：2015-10-29
--作者：xipxop
--修改：调整了部分参数

-----------------------------------------------------

-- 金币附魔
function equipment_enchant_normal(equipment_quality)
  
	-- 装备的品质越高，其附魔消耗就越高
	
	local consume_gold = 10000 + (equipment_quality - 1) * 1000
	local consume_score = math.floor(consume_gold/80)
	
	return consume_gold, consume_score

end

-- 水晶附魔
function equipment_enchant_diamond(equipment_quality)
  
	-- 装备的品质越高，其附魔消耗就越高

	local consume_diamond = 200 + (equipment_quality - 1) * 10
	
	return consume_diamond

end
