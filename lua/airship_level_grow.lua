----------------������ʷ------------------------------
--��ͧ�������Գɳ���ʽ
--���ڣ�2014-06-18
--���ߣ����Ʒ�
--�޸ģ������ű�


-----�ɳ�����1�����ɳ�--------------------------
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

-----�ɳ�����2���߳ɳ���--------------------------
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

-----�ɳ�����3���ͳɳ���--------------------------
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

-----�ɳ�����4�����ͳɳ�--------------------------
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


-----�ɳ�����5�����ɳ���--------------------------
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
