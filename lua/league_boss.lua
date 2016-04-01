----------------更新历史------------------------------
--日期：2015-08-04
--作者：xipxop
--修改：创建脚本


function league_boss_hp(slot, ability)
	
	local hp

	if slot == 1 then
	   hp = ability * 100
	elseif slot == 2 then
	   hp = ability * 200
	elseif slot == 3 then
	   hp = ability * 300
	elseif slot == 4 then
	   hp = ability * 400
	elseif slot == 5 then
	   hp = ability * 500
	elseif slot == 6 then
	   hp = ability * 600
	elseif slot == 7 then
	   hp = ability * 700
	elseif slot == 8 then
	   hp = ability * 800
	elseif slot == 9 then
	   hp = ability * 900
	elseif slot == 10 then
	   hp = ability * 1000
	elseif slot == 11 then
	   hp = ability * 1100
	elseif slot == 12 then
	   hp = ability * 1200
	else
	   hp = ability * 2000
	end
	
    hp = math.min(hp, 10000000 * slot)

    return hp
    
end


-- 伤害警报
function league_boss_alert(slot, ability)

	local alert_damage
	local atk_ability

	atk_ability = ability * 0.2

	if slot == 1 then
	   alert_damage = (atk_ability * 500 / 650) * 12 * 5 * 60
	elseif slot == 2 then
	   alert_damage = (atk_ability * 500 / 900) * 12 * 5 * 60
	elseif slot == 3 then
	   alert_damage = (atk_ability * 500 / 6500) * 12 * 5 * 60
	else
	   alert_damage = (atk_ability * 500 / 10000) * 12 * 5 * 60
	end 

	return alert_damage

end

-- 战斗结束奖励
function league_boss_everyrew(total_damage, slot ,monster_hp_limit)

	local Damage = tonumber(total_damage)
	local Monster_Index = tonumber(slot)
	-- local honor

	--honor = math.max(math.min(math.floor((Damage/10)),35000),3000)

	local Box_Index = 10004

	if Monster_Index == 1 then
		if Damage > 20000 then
		   Box_Index = 4040102
		else
		   Box_Index = 101031
		end
	elseif Monster_Index == 2 then
		if Damage > 20000 then
		   Box_Index = 4040202
		elseif Damage < 2000 then
		   Box_Index = 101031
		else
		   Box_Index = 4040102
	    end
	elseif Monster_Index == 3 then
		if Damage > 20000 then
		   Box_Index = 4040302
		elseif Damage > 5000 and Damage <= 20000 then
		   Box_Index = 4040202
		elseif Damage < 1000 then
		   Box_Index = 101031
		else
		   Box_Index = 4040102
		end
	elseif Monster_Index == 4040401 then
		Box_Index = 101031
	end

	return 15,Box_Index

end