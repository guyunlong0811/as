----------------������ʷ------------------------------
--�����������ս������ű�
--���ڣ�2014-06-18
--���ߣ����Ʒ�
--�޸ģ������ű�

math.randomseed(tostring(os.time()):reverse():sub(1, 6))

function league_quest_combat(player_level,quest_type,last_num)--���Σ���ҵ�ǰ�ȼ����������ͣ����������һ��������
	local level = tonumber(player_level)
	local chazhi = tonumber(last_num)
	local type_quest = tonumber(quest_type)
	local rank = 0
	if type_quest == 1 then
		rank = math.random(1,chazhi)
	else
		if chazhi < 150 then
			rank = math.random(1,chazhi)
		else
			rank = math.random(1,150)
		end
	end
	print(rank)
	return rank
end


function league_quest_elite(target_name,player_name,league_name)
	return "fsd","fsa","ewi",1,100,0,0,0,0,0,0,0,0,0,0,90
end
