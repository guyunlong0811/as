----------------更新历史------------------------------
--飞艇升级属性成长公式
--日期：2014-06-18
--作者：李云飞
--修改：创建脚本


-----成长类型1：最大成长--------------------------
function airship_level_grow_1(basic_value,level)

local curr_value
local step
local i

step = 0

for i = 1,level do
    step = step + 0.05 + math.floor(i/14.5)*0.01
end

curr_value = math.floor(basic_value * (1+step))+1

return curr_value

end

-----成长类型2：高成长型--------------------------
function airship_level_grow_2(basic_value,level)

local curr_value
local step
local i

step = 0

for i = 1,level do
    step = step + 0.05 + math.floor(i/14.5)*0.01
end

step = step*533/800

curr_value = math.floor(basic_value * (1+step))+1

return curr_value

end

-----成长类型3：低成长型--------------------------
function airship_level_grow_3(basic_value,level)

local curr_value
local step
local i

step = 0

for i = 1,level do
    step = step + 0.05 + math.floor(i/14.5)*0.01
end

step = step*355/800

curr_value = math.floor(basic_value * (1+step))+1

return curr_value

end

-----成长类型4：极低成长--------------------------
function airship_level_grow_4(basic_value,level)

local curr_value
local step
local i

step = 0

for i = 1,level do
    step = step + 0.05 + math.floor(i/14.5)*0.01
end

step = step*237/800

curr_value = math.floor(basic_value * (1+step))+1

return curr_value

end


-----成长类型5：不成长型--------------------------
function airship_level_grow_5(basic_value,level)

local curr_value
local step
local i

step = 0

for i = 1,level do
    step = step + 0.05 + math.floor(i/14.5)*0.01
end

step = step*0/800

curr_value = math.floor(basic_value * (1+step))

return curr_value

end
