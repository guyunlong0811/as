----------------更新历史----------------
--日期：2014-06-20
--失灭之战关卡设定脚本
--作者：李云飞
--修改：创建脚本
--返回货币类型：1，金币，2钻石，3联盟贡献度，4竞技场荣誉值



----------------失灭之战关卡设定----------------
function league_area_battle(player_level)
	local level = tonumber(player_level)
	if level <= 40 then
		return 301
	else
		return 302
	end
end

function league_area_battle_win(instance_index,player_name,league_name) --占领奖励
	return "fsd","fsa","ewi",1,100,0,0,0,0,0,0,0,0,0,0,90
end

--function league_battle_win_leaguereward(level) --每日联盟奖励
--	local a = level * 100
--	return 1000
--end

function league_battle_win_playerreward(name,league_name) --每日人员奖励
	return "fsd","fsa","ewi",1,100,0,0,0,0,0,0,0,0,0,0,90
end

function league_battle_leaguereward(points) --结算联盟奖励
	local points_count = points/100
	local gold =  5000 * points_count
	return gold
end
