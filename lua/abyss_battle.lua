----------------更新历史------------------------------
--日期：2014-07-30
--作者：liyunfei
--修改：创建脚本

--日期：2015-07-10
--作者：l
--修改：创建脚本

-----------------------------------------------------

function abyss_monster_hp(monster_id, top_100_ability, alive_period)

	local hp

	if monster_id == 4040101 then
       hp = (top_100_ability / 25 * 20 * 5 * 60) * 10
    elseif monster_id == 4040201 then
       hp = (top_100_ability / 25 * 20 * 5 * 60) * 20
    elseif monster_id == 4040301 then
       hp = (top_100_ability / 25 * 20 * 5 * 60) * 50
    elseif monster_id == 4040401 then
       hp = (top_100_ability / 25 * 20 * 5 * 60) * 40
    else
       hp = (top_100_ability / 25 * 20 * 5 * 60) * 100
    end

    hp = math.min(hp, 1000000000)
    
	return hp

end


function abyss_damage_alert(monster_id, top_5_ability)

	local alert_damage
	local atk_ability

	atk_ability = top_5_ability * 0.2

	if monster_id == 4040101 then
	   alert_damage = (atk_ability * 500 / 650) * 12 * 5 * 60
	elseif monster_id == 4040201 then
	   alert_damage = (atk_ability * 500 / 900) * 12 * 5 * 60
	elseif monster_id == 4040301 then
	   alert_damage = (atk_ability * 500 / 3250) * 12 * 5 * 60
	elseif monster_id == 4040401 then
	   alert_damage = (atk_ability * 500 / 6500) * 12 * 5 * 60
	else
	   alert_damage = (atk_ability * 500 / 10000) * 12 * 5 * 60
	end 

	return alert_damage

end


function abyss_battle_everyrew(dps,index,monster_hp_limit) --战斗结束奖励

	local Damage = tonumber(dps)
	local Monster_Index = tonumber(index)
    
    Gold = math.max(math.min(math.floor((Damage/10)),35000),3000)	

	local Box_Index = 10004

	if Monster_Index == 4040101 then
		if Damage > 20000 then
		   Box_Index = 4040102
		else
		   Box_Index = 101031
		end
	elseif Monster_Index == 4040201 then
		if Damage > 20000 then
		   Box_Index = 4040202
		elseif Damage < 2000 then
		   Box_Index = 101031
		else
		   Box_Index = 4040102
	    end
    elseif Monster_Index == 4040401 then
		if Damage > 20000 then
		   Box_Index = 4040302
		elseif Damage > 5000 and Damage <= 20000 then
		   Box_Index = 4040202
		elseif Damage < 1000 then
		   Box_Index = 101031
		else
		   Box_Index = 4040102
		end
	elseif Monster_Index == 4040301 then
		if Damage > 20000 then
		   Box_Index = 4040302
		elseif Damage > 5000 and Damage <= 20000 then
		   Box_Index = 4040202
		elseif Damage < 1000 then
		   Box_Index = 101031
		else
		   Box_Index = 4040102
		end
	else
		Box_Index = 101031
		Gold = 0
	end

	return 0,Gold,Box_Index

end