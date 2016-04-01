----------------更新历史------------------------------
--日期：2014-04-12
--作者：xipxop
--修改：创建脚本

--日期：2015-11-09
--作者：xipxop
--修改：提升伙伴觉醒时的属性成长幅度

-----成长类型1：最大成长--------------------------
function partner_level_grow_1(basic_value,init_value,level,favour)

local curr_value
local step
local favour_step

step = 0
favour_step = 0

local i
local j 

-- calculate level up 
for i = 1, level-1 do
    step = step + 0.06 + math.floor(i/14.5)*0.01381
end

-- calculate favour add
for j = 1, math.floor(favour/1000) do 
    favour_step = favour_step + 1 + (j-1)*0.5
end

curr_value = (math.floor(basic_value * step + init_value * favour_step) + basic_value) * 1

return curr_value

end

-----成长类型2：高成长型--------------------------
function partner_level_grow_2(basic_value,init_value,level,favour)

local curr_value
local step
local favour_step

step = 0
favour_step = 0

local i
local j 

for i = 1, level-1 do
    step = step + 0.06 + math.floor(i/14.5)*0.01381
end 
step = step*700/1000

for j = 1, math.floor(favour/1000) do 
    favour_step = favour_step + 1 + (j-1)*0.5
end
favour_step = favour_step*630/900

curr_value = (math.floor(basic_value * step + init_value * favour_step) + basic_value) * 1

return curr_value

end

-----成长类型3：低成长型--------------------------
function partner_level_grow_3(basic_value,init_value,level,favour)

local curr_value
local step
local favour_step

step = 0
favour_step = 0

local i
local j 

for i = 1, level-1 do
    step = step + 0.06 + math.floor(i/14.5)*0.01381
end 
step = step*500/1000

for j = 1, math.floor(favour/1000) do 
    favour_step = favour_step + 1 + (j-1)*0.5
end
favour_step = favour_step*450/900

curr_value = (math.floor(basic_value * step + init_value * favour_step) + basic_value) * 1

return curr_value

end

-----成长类型4：极低成长--------------------------
function partner_level_grow_4(basic_value,init_value,level,favour)

local curr_value
local step
local favour_step

step = 0
favour_step = 0

local i
local j 

for i = 1, level-1 do
    step = step + 0.06 + math.floor(i/14.5)*0.01381
end 
step = step*300/1000

for j = 1, math.floor(favour/1000) do 
    favour_step = favour_step + 1 + (j-1)*0.5
end
favour_step = favour_step*270/900

curr_value = (math.floor(basic_value * step + init_value * favour_step) + basic_value) * 1

return curr_value

end


-----成长类型5：不成长型--------------------------
function partner_level_grow_5(basic_value,init_value,level,favour)

local curr_value
local step
local favour_step

step = 0
favour_step = 0

local i
local j 

for i = 1, level-1 do
    step = step + 0.06 + math.floor(i/14.5)*0.01381
end 
step = step*0/1000

for j = 1, math.floor(favour/1000) do 
    favour_step = favour_step + 1 + (j-1)*0.5
end
favour_step = favour_step*0/900

curr_value = (math.floor(basic_value * step + init_value * favour_step) + basic_value) * 1

return curr_value

end

-- print(partner_level_grow_1(129,129,15,3000))