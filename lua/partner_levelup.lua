----------------更新历史------------------------------
--日期：2014-04-17
--作者：xipxop
--修改：创建脚本
------------------------------------------------------
--日期：2014-04-21
--作者：xipxop
--修改：根据设定，修改函数中的计算公式
------------------------------------------------------
--日期：2014-11-13
--作者：xipxop
--修改：根据设定，修改函数中的计算公式


-----成长类型1：超早熟--------------------------
function partner_levelup_1(level)

local need_exp

need_exp = 0.00043*level*level*level*level - 0.005*level*level*level + 5*level*level - 0.03*level + 15

need_exp = math.ceil(need_exp)

return need_exp

end

-----成长类型2：早熟--------------------------
function partner_levelup_2(level)

local need_exp

need_exp = 0.00049*level*level*level*level - 0.005*level*level*level + 5*level*level + 1.95*level + 13

need_exp = math.ceil(need_exp)

return need_exp

end

-----成长类型3：普通--------------------------
function partner_levelup_3(level)

local need_exp

if level == 1 then
   need_exp = 10
elseif level == 2 then
   need_exp = 20
elseif level == 3 then
   need_exp = 20
elseif level == 4 then
   need_exp = 20
elseif level == 5 then
   need_exp = 20
elseif level == 6 then
   need_exp = 40
elseif level == 7 then
   need_exp = 100
else
   need_exp = 0.00055*level*level*level*level - 0.0055*level*level*level + 5.02*level*level + 4.97*level + 10
end

need_exp = math.ceil(need_exp)

return need_exp

end

-----成长类型4：晚成--------------------------
function partner_levelup_4(level)

local need_exp

need_exp = 0.00068*level*level*level*level - 0.0065*level*level*level + 5.04*level*level + 10*level + 4.9

need_exp = math.ceil(need_exp)

return need_exp

end

-----成长类型5：超晚成--------------------------
function partner_levelup_5(level)

local need_exp

need_exp = 0.0008*level*level*level*level - 0.007*level*level*level + 5.08*level*level + 15*level - 0.1

need_exp = math.ceil(need_exp) 

return need_exp

end