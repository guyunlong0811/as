----------------更新历史------------------------------
--日期：2016-03-28
--作者：xipxop
--修改：调整10035的技能描述
--修改：增加10275的技能描述参数

--日期：2016-03-29
--作者：xipxop
--修改：调整10004的技能数值算法
--修改：调整10395的技能数值算法
--修改：调整10095的技能数值算法
--修改：调整10515的技能数值算法
--修改：调整10505的技能数值算法
--修改：调整10225的技能数值算法
--修改：调整10115的技能数值算法

--日期：2016-03-31
--作者：xipxop
--修改：调整10263的技能数值算法

-----------------------------------------------------

function skill_des(skill_index, skill_level, attri)

local des_value = {}

local attacker_curr_attack = attri:atk()
local attacker_curr_magic = attri:magic()

skill_level = skill_level - 1

-- 1000
if skill_index == 10002 then
   des_value[1] = 3.35*0.2 * (1 + skill_level * 0.04 * 2) * 10
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10003 then
   des_value[1] = (5000 + skill_level * 50) / 1000 
end

if skill_index == 10004 then
   des_value[1] = 6 + skill_level * 6
end

if skill_index == 10005 then
   des_value[1] = 10 + skill_level * 2
end

-- 1001
if skill_index == 10012 then
   des_value[1] = 1.32*0.2 * (1 + skill_level * 0.04 * 2) * 4
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10013 then
   des_value[1] = 1.66*0.2 * (1 + skill_level * 0.04 * 2) * 17
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10014 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10015 then
   des_value[1] = 5 + skill_level * 0.5
end

-- 1002
if skill_index == 10022 then
   des_value[1] = math.floor(attacker_curr_magic * 0.3 + 100 * ( 1 + skill_level * 0.1))
end

if skill_index == 10023 then
   des_value[1] = math.floor(attacker_curr_magic * 0.2 + 100 * ( 1 + skill_level * 0.1))
end

if skill_index == 10024 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10025 then
   des_value[1] = 50 + skill_level * 3
end

-- 1003
if skill_index == 10032 then
   des_value[1] = 1.8*0.2 * (1 + skill_level * 0.04 * 2) * 3
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10033 then
   des_value[1] = 2.555*0.2 * (1 + skill_level * 0.04 * 2) * 11
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10034 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10035 then
   des_value[1] = 200 + skill_level * 15
end

-- 1004
if skill_index == 10042 then
   des_value[1] = 1.1*0.2 * (1 + skill_level * 0.04 * 2) * 7
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10043 then
   des_value[1] = 2.25*0.2 * (1 + skill_level * 0.04 * 2) * 11
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10044 then
   des_value[1] = 20 * skill_level + 3
end

if skill_index == 10045 then
   des_value[1] = 20 + skill_level * 2
end

-- 1005
if skill_index == 10052 then
   des_value[1] = 0.65*0.2 * (1 + skill_level * 0.04 * 2) * 6 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10053 then
   des_value[1] = 1.512*0.2 * (1 + skill_level * 0.04 * 2) * 17 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10054 then
   des_value[1] = 10 * skill_level + 2
end

if skill_index == 10055 then
   des_value[1] = 1 + skill_level * 0.5
end

-- 1006
if skill_index == 10062 then
   des_value[1] = 0.7*0.2 * (1 + skill_level * 0.04 * 2) * 9 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10063 then
   des_value[1] = 1.456*0.2 * (1 + skill_level * 0.04 * 2) * 18 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10064 then
   des_value[1] = 100 + skill_level * 2
end

if skill_index == 10065 then
   des_value[1] = skill_level + 1
end

-- 1007
if skill_index == 10072 then
   des_value[1] = 0.3*0.2 * (1 + skill_level * 0.04 * 2) * 12 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10073 then
   des_value[1] = 2.4*0.2 * (1 + skill_level * 0.04 * 2) * 11 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10074 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10075 then
   des_value[1] = (skill_level + 1) * 0.5
end

-- 1008
if skill_index == 10082 then
   des_value[1] = 0.6*0.2 * (1 + skill_level * 0.04 * 2) * 11
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10083 then
   des_value[1] = 1.62*0.2 * (1 + skill_level * 0.04 * 2) * 16 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10084 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10085 then
   des_value[1] = 5 + skill_level * 0.5
end

-- 1009
if skill_index == 10092 then
   des_value[1] = 1.3*0.2 * (1 + skill_level * 0.04 * 2) * 4
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10093 then
   des_value[1] = 2.358*0.2 * (1 + skill_level * 0.04 * 2) * 12 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10094 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10095 then
   des_value[1] = 50 + skill_level * 2
end

-- 1011
if skill_index == 10112 then
   des_value[1] = 1.3*0.2 * (1 + skill_level * 0.04 * 2) * 5
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10113 then
   des_value[1] = 2.25*0.2 * (1 + skill_level * 0.04 * 2) * 12 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10114 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10115 then
   des_value[1] = skill_level + 1
end

-- 1012
if skill_index == 10122 then
   des_value[1] = 1.2*0.2 * (1 + skill_level * 0.04 * 2) * 8
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10123 then
   des_value[1] = 1.707*0.2 * (1 + skill_level * 0.04 * 2) * 14 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10124 then
   des_value[1] = 100 + skill_level * 20
end

if skill_index == 10125 then
   des_value[1] = 2 + skill_level * 0.2
end

-- 1016
if skill_index == 10162 then
   des_value[1] = 1.1*0.2 * (1 + skill_level * 0.04 * 2) * 5
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10163 then
   des_value[1] = 1.625*0.2 * (1 + skill_level * 0.04 * 2) * 16 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10164 then
   des_value[1] = 10 + skill_level * 5
end

if skill_index == 10165 then
   des_value[1] = skill_level * 5 + 50
end

-- 1017
if skill_index == 10172 then
   des_value[1] = 1.2*0.2 * (1 + skill_level * 0.04 * 2) * 6 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10173 then
   des_value[1] = 1.488*0.2 * (1 + skill_level * 0.04 * 2) * 17
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10174 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10175 then
   des_value[1] = 10 + skill_level * 2
end

-- 1019
if skill_index == 10192 then
   des_value[1] = 2*0.2 * (1 + skill_level * 0.04 * 2) * 3 
   des_value[1] = math.floor(des_value[1] * 100)
end

if skill_index == 10193 then
   des_value[1] = 5.5*0.2 * (1 + skill_level * 0.04 * 2) * 5
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10194 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10195 then
   des_value[1] = (skill_level + 1) * 2
end

-- 1021
if skill_index == 10212 then
   des_value[1] = 1.1*0.2 * (1 + skill_level * 0.04 * 2) * 7
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10213 then
   des_value[1] = 1.29*0.2 * (1 + skill_level * 0.04 * 2) * 20
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10214 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10215 then
   des_value[1] = (skill_level + 1) * 2
end

-- 1022
if skill_index == 10222 then
   des_value[1] = 3*0.2 * (1 + skill_level * 0.04 * 2) * 2
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10223 then
   des_value[1] = 3.929*0.2 * (1 + skill_level * 0.04 * 2) * 7
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10224 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10225 then
   des_value[1] = (skill_level + 1) * 5
end

-- 1024
if skill_index == 10242 then
   des_value[1] = 0.3*0.2 * (1 + skill_level * 0.04 * 2) * 12
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10243 then
   des_value[1] = 2.2*0.2 * (1 + skill_level * 0.04 * 2) * 12
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10244 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10245 then
   des_value[1] = 20 + skill_level
end

-- 1025
if skill_index == 10252 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 3 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10253 then
   des_value[1] = 1.7*0.2 * (1 + skill_level * 0.04 * 2) * 16
   des_value[1] = math.floor(des_value[1] * 100) 
   des_value[2] = skill_level * 10 + 500
end

if skill_index == 10254 then
   des_value[1] = 100 + skill_level * 2
end

if skill_index == 10255 then
   des_value[1] = 10 + skill_level * 1.5
end

-- 1026
if skill_index == 10262 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 5 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10263 then
   des_value[1] = math.floor(attacker_curr_magic * 0.3 + ( 1 + skill_level) * 100)
   des_value[2] = 1000 + skill_level * 100
end

if skill_index == 10264 then
   des_value[1] = 50 + skill_level * 5
end

if skill_index == 10265 then
   des_value[1] = 5 + skill_level * 0.5
end

-- 1027
if skill_index == 10272 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 4 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10273 then
   des_value[1] = 2.292*0.2 * (1 + skill_level * 0.04 * 2) * 12 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10274 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10275 then
   des_value[1] = 10 + skill_level * 2
   des_value[2] = 50 + skill_level * 2
end

-- 1028
if skill_index == 10282 then
   des_value[1] = 1.4*0.2 * (1 + skill_level * 0.04 * 2) * 3 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10283 then
   des_value[1] = 1.96*0.2 * (1 + skill_level * 0.04 * 2) * 15
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10284 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10285 then
   des_value[1] = 200 + skill_level * 10
end

-- 1029
if skill_index == 10292 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 6 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10293 then
   des_value[1] = 3.5*0.2 * (1 + skill_level * 0.04 * 2) * 7
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10294 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10295 then
   des_value[1] = 2 + skill_level * 2
end

-- 1030
if skill_index == 10302 then
   des_value[1] = 1.16*0.2 * (1 + skill_level * 0.04 * 2) * 10 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10303 then
   des_value[1] = 1.422*0.2 * (1 + skill_level * 0.04 * 2) * 14
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10304 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10305 then
   des_value[1] = skill_level * 2
end

-- 1031
if skill_index == 10312 then
   des_value[1] = 1.375*0.2 * (1 + skill_level * 0.04 * 2) * 4
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10313 then
   des_value[1] = 2*0.2 * (1 + skill_level * 0.04 * 2) * 14 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10314 then
   des_value[1] = 50 + skill_level * 8
end

if skill_index == 10315 then
   des_value[1] = 20 + skill_level * 2
end

-- 1034
if skill_index == 10342 then
   des_value[1] = 1.2*0.2 * (1 + skill_level * 0.04 * 2) * 3 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10343 then
   des_value[1] = 2.9*0.2 * (1 + skill_level * 0.04 * 2) * 10 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10344 then
   des_value[1] = 50 + skill_level * 8
end

if skill_index == 10345 then
   des_value[1] = (skill_level+1) * 2
end

-- 1036
if skill_index == 10362 then
   des_value[1] = 1.3*0.2 * (1 + skill_level * 0.04 * 2) * 6 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10363 then
   des_value[1] = 1.544*0.2 * (1 + skill_level * 0.04 * 2) * 16 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10364 then
   des_value[1] = 100 + skill_level * 10
end

if skill_index == 10365 then
   des_value[1] = 200 + (skill_level+1) * 20
end

-- 1038
if skill_index == 10382 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 4 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10383 then
   des_value[1] = 2.292*0.2 * (1 + skill_level * 0.04 * 2) * 12
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10384 then
   des_value[1] = 50 + skill_level * 5
end

if skill_index == 10385 then
   des_value[1] = skill_level + 1
end

-- 1039
if skill_index == 10392 then
   des_value[1] = 1.3*0.2 * (1 + skill_level * 0.04 * 2) * 4 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10393 then
   des_value[1] = 1.664*0.2 * (1 + skill_level * 0.04 * 2) * 17
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10394 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10395 then
   des_value[1] = 500 + skill_level * 500
end

-- 1040
if skill_index == 10402 then
   des_value[1] = 1.3*0.2 * (1 + skill_level * 0.04 * 2) * 9
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10403 then
   des_value[1] = 1.82*0.2 * (1 + skill_level * 0.04 * 2) * 12 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10404 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10405 then
   des_value[1] = 200 + skill_level * 20
end

-- 1041
if skill_index == 10412 then
   des_value[1] = 1.28*0.2 * (1 + skill_level * 0.04 * 2) * 5 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10413 then
   des_value[1] = 1.594*0.2 * (1 + skill_level * 0.04 * 2) * 17 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10414 then
   des_value[1] = 100 + skill_level * 10
end

if skill_index == 10415 then
   des_value[1] = (skill_level + 1) * 3
end

-- 1043
if skill_index == 10432 then
   des_value[1] = 1.35*0.2 * (1 + skill_level * 0.04 * 2) * 3
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10433 then
   des_value[1] = 1.64*0.2 * (1 + skill_level * 0.04 * 2) * 18 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10434 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10435 then
   des_value[1] = (skill_level + 1) * 5
end

-- 1044
if skill_index == 10442 then
   des_value[1] = 0.4*0.2 * (1 + skill_level * 0.04 * 2) * 8 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10443 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 16 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10444 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10445 then
   des_value[1] = (skill_level + 1) * 0.5
end

-- 1045
if skill_index == 10452 then
   des_value[1] = 0.65*0.2 * (1 + skill_level * 0.04 * 2) * 12
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10453 then
   des_value[1] = 2.35*0.2 * (1 + skill_level * 0.04 * 2) * 11 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10454 then
   des_value[1] = 100 + skill_level * 100
end

if skill_index == 10455 then
   des_value[1] = 20 + skill_level * 2
end

-- 1046
if skill_index == 10462 then
   des_value[1] = 1.5*0.2 * (1 + skill_level * 0.04 * 2) * 4
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10463 then
   des_value[1] = 2.45*0.2 * (1 + skill_level * 0.04 * 2) * 10
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10464 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10465 then
   des_value[1] = (skill_level + 1) * 5
end

-- 1047
if skill_index == 10472 then
   des_value[1] = 1.3*0.2 * (1 + skill_level * 0.04 * 2) * 9
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10473 then
   des_value[1] = 1.982*0.2 * (1 + skill_level * 0.04 * 2) * 11
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10474 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10475 then
   des_value[1] = 100 + skill_level * 10
end

-- 1048
if skill_index == 10482 then
   des_value[1] = 0.6*0.2 * (1 + skill_level * 0.04 * 2) * 5
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10483 then
   des_value[1] = 1.736*0.2 * (1 + skill_level * 0.04 * 2) * 17
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10484 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10485 then
   des_value[1] = 1 + skill_level * 0.5
end

-- 1049
if skill_index == 10492 then
   des_value[1] = 1.1*0.2 * (1 + skill_level * 0.04 * 2) * 8 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10493 then
   des_value[1] = 1.372*0.2 * (1 + skill_level * 0.04 * 2) * 18 
   des_value[1] = math.floor(des_value[1] * 100) 
end

if skill_index == 10494 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10495 then
   des_value[1] = (skill_level + 1) * 5
end

-- 1050
if skill_index == 10502 then
   des_value[1] = 1.6*0.2 * (1 + skill_level * 0.04 * 2) * 5
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10503 then
   des_value[1] = 2.318*0.2 * (1 + skill_level * 0.04 * 2) * 11
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10504 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10505 then
   des_value[1] = skill_level * 5
end

-- 1051
if skill_index == 10512 then
   des_value[1] = 1.4*0.2 * (1 + skill_level * 0.04 * 2) * 4
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10513 then
   des_value[1] = 2.325*0.2 * (1 + skill_level * 0.04 * 2) * 12
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10514 then
   des_value[1] = 10 + skill_level * 2
end

if skill_index == 10515 then
   des_value[1] = skill_level * 0.5
end

-- 1052
if skill_index == 10522 then
   des_value[1] = 0.5*0.2 * (1 + skill_level * 0.04 * 2) * 5
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10523 then
   des_value[1] = 2*0.2 * (1 + skill_level * 0.04 * 2) * 13
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10524 then
   des_value[1] = 20 + skill_level * 3
end

if skill_index == 10525 then
   des_value[1] = skill_level * 0.5
end

-- 1053
if skill_index == 10532 then
   des_value[1] = 2*0.2*0.5 * (1 + skill_level * 0.04 * 2) * 4
   des_value[1] = math.floor(des_value[1] * 100)  
end

if skill_index == 10533 then
   des_value[1] = math.floor(attacker_curr_magic * 0.3 + 100 * ( 1 + skill_level))
end

if skill_index == 10534 then
   des_value[1] = 500 + skill_level * 30
end

if skill_index == 10535 then
   des_value[1] =  1 + skill_level
end

-- return
return des_value

end