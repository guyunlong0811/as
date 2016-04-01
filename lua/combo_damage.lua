----------------更新历史------------------------------
--日期：2015-07-08
--作者：xipxop
--修改：创建脚本

--日期：2015-08-08
--作者：xipxop
--修改：分离竞技场模式和审判之门模式下的combo-damage转换

------------------------------------------------------

function fnComboToDamagePercent(comboHit)

	local damage_percent
	local damage_percent_final
	local result

	local battle_mode = ZTDataDirector:sharedDirector():getBattleModel()

	if battle_mode == ZTBattleModelBabel then
       damage_percent = comboHit^0.83 + math.log10(comboHit)
       damage_percent_final = math.min(damage_percent, 300)
    elseif battle_mode == ZTBattleModelArene then
       damage_percent = 0
       damage_percent_final = math.min(damage_percent, 100)
    elseif battle_mode == ZTBattleModelLifeDeath then
       damage_percent = 0
       damage_percent_final = math.min(damage_percent, 100)
    else
       damage_percent = comboHit^0.83 + math.log10(comboHit)
       damage_percent_final = math.min(damage_percent, 300)
    end

    result = (damage_percent_final + 100) / 100

    if result < 1 then
       result = 1
    end

	return result

end

