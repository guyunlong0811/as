----------------更新历史------------------------------
--日期：2014-08-05
--作者：xipxop
--修改：创建脚本

--日期：2014-11-25
--作者：xipxop
--修改：将免费首抽调整为必定出伙伴；为金币十连抽和水晶十连抽增加了上下限保护

--日期：2015-05-29
--作者：xipxop
--修改：将抽卡结果与战队等级挂钩

--日期：2015-07-08
--作者：xipxop
--修改：根据消费分割奖池，根据累计消费给予保底奖励

--日期：2015-07-18
--作者：xipxop
--修改：细化所有抽卡，根据等级、消费（次数）、VIP、是否首抽等分别定义抽卡产出

--日期：2016-01-05
--作者：xipxop
--修改：水晶十连抽，每第5次必出SS伙伴

----------------全局参数------------------------------

-- 用于区分金币抽卡产出区分的战队等级参数
TEAM_LIMIT_1 = 15
TEAM_LIMIT_2 = 30

-----------------------------------------------------

-----------------------------------------------------
-- 金币单抽
-----------------------------------------------------
function pray_1001(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local pray_value
local bonus = {}

local pray_count = current_pay_times + current_free_times

-- 首次免费抽卡为新手引导，固定出1038
if is_free == 1 and pray_count == 0 then
   pray_value = 31001
end

-- 15级以前
-- 只出对这个阶段玩家有用的产出
-- 防止刷抽卡，大量注册，拉底留存：不出整卡
-- 如果是免费抽卡，那么只会出：赏金、卖钱道具、经验药剂、白色精炼材料
-- 如果是付费抽卡，那么还会出：时空灰烬、伙伴魂石
-- 1. 如果抽卡次数少于5次，只会出A资质伙伴魂石
-- 2. 如果抽卡次数多于5次，还会出S资质伙伴魂石
if team_level <= TEAM_LIMIT_1 and pray_count ~= 0 then
   if is_free == 1 then
   	  pray_value = 31002
   else
   	  if current_pay_times <= 5 then
   	     pray_value = 31003
   	  else
   	  	 pray_value = 31004
   	  end
   end
end

-- 15级以后，30级以前
-- 如果是免费抽卡
-- 1. 如果抽卡总次数少于15次（免费抽卡3天内），那么只会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、纹章材料、时空灰烬、伙伴魂石（A、S）
-- 2. 如果抽卡总次数多于15次（免费抽卡3天后），那么还会出：伙伴整卡（仅限A资质伙伴）
-- 如果是付费抽卡
-- 1. 如果抽卡总次数少于9次（18万金币消耗以内），那么只会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、纹章材料、时空灰烬、伙伴魂石（A、S）
-- 2. 如果抽卡总次数多于9次（18万金币消耗以上），那么还会出：伙伴整卡（A资质伙伴、小几率S资质伙伴）
if team_level > TEAM_LIMIT_1 and team_level <= TEAM_LIMIT_2 and pray_count ~= 0 then
   if is_free == 1 then
   	  if current_free_times <= 15 then
   	     pray_value = 31005
   	  else
   	  	 pray_value = 31006
   	  end
   else
   	  if current_pay_times <= 9 then
   	     pray_value = 31007
   	  else
   	  	 pray_value = 31008
   	  end
   end 
end

-- 30级以后，在15级的基础上会增加星灵强化和洗练材料的掉落
-- 如果是免费抽卡
-- 1. 如果抽卡总次数少于15次（免费抽卡3天内），那么只会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、星灵强化材料、星灵洗练材料、伙伴魂石
-- 2. 如果抽卡总次数多于15次（免费抽卡3天后），那么还会出：伙伴整卡（仅限A资质伙伴）
-- 如果是付费抽卡
-- 1. 如果抽卡总次数少于9次（18万金币消耗以内），那么只会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、星灵强化材料、星灵洗练材料、伙伴魂石
-- 2. 如果抽卡总次数多于9次（18万金币消耗以上），那么还会出：伙伴整卡（A资质伙伴、小几率S资质伙伴）
if team_level > TEAM_LIMIT_2 and pray_count ~= 0 then
   if is_free == 1 then
   	  if current_free_times <= 15 then
   	     pray_value = 31009
   	  else
   	  	 pray_value = 31010
   	  end
   else
   	  if current_pay_times <= 9 then
   	     pray_value = 31011
   	  else
   	  	 pray_value = 31012
   	  end
   end 
end

-- 金币单抽不区分VIP
bonus[1] = pray_value

-- print(bonus[1])

return bonus

end

-----------------------------------------------------
-- 金币十连
-----------------------------------------------------
function pray_1010(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local count
local bonus = {}

local pray_count = current_pay_times
local partner_count = 0

local prop
math.randomseed(tostring(os.time()):reverse():sub(1, 6)) 

-- 首次金币十连
-- 必出一位伙伴（限定为A资质）
-- 只出一位伙伴
-- 15级以前，只出对这个阶段玩家有用的产出：赏金、卖钱道具、经验药剂、白色精炼材料、时空灰烬、伙伴魂石（仅限A资质）
-- 15级以后，会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、纹章材料、时空灰烬、伙伴魂石（仅限A资质）
-- 30级以后，会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、星灵强化材料、星灵洗练材料、伙伴魂石（仅限A资质）
if pray_count == 0 then
   bonus[1] = 32001
   for count = 2, 10 do
   	   if team_level <= TEAM_LIMIT_1 then
   	      bonus[count] = 32002
   	   elseif team_level > TEAM_LIMIT_1 and team_level <= TEAM_LIMIT_2 then
   	   	  bonus[count] = 32003
   	   else
   	   	  bonus[count] = 32004
   	   end
   end
end

-- 前10次金币十连
-- 没有保底伙伴产出
-- 最多只会产出一位伙伴
-- 15级以前，只出对这个阶段玩家有用的产出：赏金、卖钱道具、经验药剂、白色精炼材料、时空灰烬、伙伴魂石（仅限A资质）、伙伴（仅限A资质）
-- 15级以后，会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、纹章材料、时空灰烬、伙伴魂石（仅限A资质）、伙伴（仅限A资质）
-- 30级以后，会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、星灵强化材料、星灵洗练材料、伙伴魂石（仅限A资质）、伙伴（仅限A资质）
if pray_count > 0 and pray_count <= 10 then
   for count = 1, 10 do
   	   if team_level <= TEAM_LIMIT_1 then
   	   	  prop = math.random(0,100)
   	   	  if prop < 3 and partner_count < 1 then 
   	         bonus[count] = 32005
   	         partner_count = partner_count + 1
   	      else
   	      	 bonus[count] = 32006
   	      end
   	   elseif team_level > TEAM_LIMIT_1 and team_level <= TEAM_LIMIT_2 then
   	   	  prop = math.random(0,100)
   	   	  if prop < 3 and partner_count < 1 then 
   	         bonus[count] = 32007
   	         partner_count = partner_count + 1
   	      else
   	      	 bonus[count] = 32008
   	      end
   	   else
   	   	  prop = math.random(0,100)
   	   	  if prop < 3 and partner_count < 1 then 
   	         bonus[count] = 32009
   	         partner_count = partner_count + 1
   	      else
   	      	 bonus[count] = 32010
   	      end
   	   end
   end
end

-- 10次后金币十连
-- 没有保底伙伴产出
-- 最多只会产出一位
-- 15级以前，只出对这个阶段玩家有用的产出：赏金、卖钱道具、经验药剂、白色精炼材料、时空灰烬、伙伴魂石（A、S）、伙伴（A、S）
-- 15级以后，会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、纹章材料、时空灰烬、伙伴魂石（A、S）、伙伴（A、S）
-- 30级以后，会出：赏金、卖钱道具、经验药剂、白色精炼材料、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、星灵强化材料、星灵洗练材料、伙伴魂石（A、S）、伙伴（A、S）
if pray_count > 10 then
   for count = 1, 10 do
   	   if team_level <= TEAM_LIMIT_1 then
   	      prop = math.random(0,100)
   	   	  if prop < 3 and partner_count < 1 then 
   	         bonus[count] = 32011
   	         partner_count = partner_count + 1
   	      else
   	      	 bonus[count] = 32012
   	      end
   	   elseif team_level > TEAM_LIMIT_1 and team_level <= TEAM_LIMIT_2 then
   	      prop = math.random(0,100)
   	   	  if prop < 3 and partner_count < 1 then 
   	         bonus[count] = 32013
   	         partner_count = partner_count + 1
   	      else
   	      	 bonus[count] = 32014
   	      end
   	   else
   	   	  prop = math.random(0,100)
   	   	  if prop < 3 and partner_count < 1 then 
   	         bonus[count] = 32015
   	         partner_count = partner_count + 1
   	      else
   	      	 bonus[count] = 32016
   	      end
   	   end
   end
end

-- print(bonus[1],bonus[2],bonus[3],bonus[4],bonus[5],bonus[6],bonus[7],bonus[8],bonus[9],bonus[10])

-- 金币十连不区分VIP
return bonus

end

-----------------------------------------------------
-- 水晶单抽
-----------------------------------------------------
function pray_2001(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local pray_value
local bonus = {}

local pray_count = current_free_times + current_pay_times

-- 首次免费抽卡为新手引导，固定出1008
if is_free == 1 and pray_count == 0 then
   pray_value = 33001
end

-- 无论免费/付费，第10次抽卡必出S/SS伙伴
-- 1. 如果累积付费抽卡次数少于10次，那么只会抽取到限定的S伙伴
-- 2. 如果累积付费抽卡次数多于10次，或者累积免费抽卡次数多于30次，那么还会抽取到其他的S伙伴
-- 3. 如果累积付费抽卡次数多于50次，或者累积免费抽卡次数多于180次，那么还会抽取到限定的SS伙伴
-- 4. 如果累积付费抽卡次数多于100次，那么还会抽取到其他的SS伙伴（不包括定向抽和竞技场限定伙伴）
if math.fmod(pray_count,10) == 0 and pray_count ~= 0 then
   if current_pay_times < 10 then   	
   	  if current_free_times < 30 then 
   	     pray_value = 33002
   	  elseif current_free_times >=30 and current_free_times < 180 then
   	  	 pray_value = 33003
   	  else
   	  	 pray_value = 33004
   	  end
   elseif current_pay_times >= 10 and current_pay_times < 50 then
   	  if current_free_times < 180 then 
   	     pray_value = 33003
   	  else
   	  	 pray_value = 33004
   	  end
   elseif current_pay_times >= 50 and current_pay_times < 100 then
   	  pray_value = 33004
   else
   	  pray_value = 33005
   end
end

-- 每第1次-第9次的抽卡情况
-- 1. 如果累积抽卡次数少于10次，那么只会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（限定S）
-- 2. 如果累积付费抽卡次数多于10次，或者累积免费抽卡次数多于30次，那么会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S）
-- 3. 如果累积付费抽卡次数多于50次，或者累积免费抽卡次数多于180次，那么会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S、限定SS）、伙伴（仅限S）
-- 4. 如果累积付费抽卡次数多于100次，那么会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S、其他SS）、伙伴（仅限S）
if math.fmod(pray_count,10) ~= 0 and pray_count ~= 0 then
   if current_pay_times < 10 then   	
   	  if current_free_times < 30 then 
   	     pray_value = 33006
   	  elseif current_free_times >=30 and current_free_times < 180 then
   	  	 pray_value = 33007
   	  else
   	  	 pray_value = 33008
   	  end
   elseif current_pay_times >= 10 and current_pay_times < 50 then
   	  if current_free_times < 180 then 
   	  	 pray_value = 33007
   	  else
   	  	 pray_value = 33008
   	  end
   elseif current_pay_times >= 50 and current_pay_times < 100 then
   	  pray_value = 33008
   else
   	  pray_value = 33009
   end
end

-- 水晶单抽不区分VIP
bonus[1] = pray_value

-- print(bonus[1])

return bonus

end

-----------------------------------------------------
-- 水晶十连
-- 必定不出：1021（定向抽）、1025（竞技场）、1034（竞技场）、1049（定向抽）
-----------------------------------------------------
function pray_2010(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local count
local bonus = {}

local partner_count = 0
local pray_count = current_free_times + current_pay_times

local prop1
-- local prop2

math.randomseed(tostring(os.time()):reverse():sub(1, 6))

-- VIP5以下
if vip_level < 5 then
	-- 首次水晶十连抽
	-- 必定会出一个伙伴：SS
	-- 必定不出两个伙伴
	if pray_count == 0 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34002
			  end    
		   else 
			  bonus[count] = 34002
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第2次十连抽
	-- 必定会出一个伙伴：S
	elseif pray_count == 1 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34001
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34002
			  end    
		   else 
			  bonus[count] = 34002
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34001
		   end 
	   end
	-- 第3-第5次十连抽
	-- 必定会出一个伙伴：S, SS
	-- 必定不出两个伙伴
	elseif pray_count > 1 and pray_count < 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34003
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34004
			  end    
		   else 
			  bonus[count] = 34004
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34003
		   end 
	   end
	-- 第6次十连抽
	-- 必定会出一个SS伙伴
	-- 必定不出两个伙伴
	elseif pray_count == 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34004
			  end    
		   else 
			  bonus[count] = 34004
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第7-10次十连抽
	-- 必定会出一个伙伴：S，SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count > 5 and pray_count < 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34005
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34006
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34007
			     end
			  else
			     bonus[count] = 34007
			  end    
		   else 
			  bonus[count] = 34007
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34005
		   end 
	   end
	-- 第11次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count == 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34006
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34007
			     end
			  else
			     bonus[count] = 34007
			  end    
		   else 
			  bonus[count] = 34007
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第12-15次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 10 and pray_count < 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34008
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34009
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34010
			     end
			  else
			     bonus[count] = 34010
			  end    
		   else 
			  bonus[count] = 34010
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34008
		   end 
	   end
	-- 第16次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count == 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34009
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34010
			     end
			  else
			     bonus[count] = 34010
			  end    
		   else 
			  bonus[count] = 34010
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第17-20次十连抽
	-- 必定会出一个伙伴：S, SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 15 and pray_count < 20 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34008
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34009
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34010
			     end
			  else
			     bonus[count] = 34010
			  end    
		   else 
			  bonus[count] = 34010
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34008
		   end 
	   end
	-- 第21次十连抽
	-- 必定会出一个伙伴：所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count == 20 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34009
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34010
			     end
			  else
			     bonus[count] = 34010
			  end    
		   else 
			  bonus[count] = 34010
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 21次后十连抽
	-- 如果是5的倍数，必定会出一个伙伴：SS
	-- 如果不是5的倍数，必定会出一个伙伴：S, SS
	-- 可能会出两个伙伴：第2个伙伴可能是S或SS
	else
	   if math.fmod(pray_count,5) == 0 then
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   -- prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34061
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34012
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34013
				     end
				  else
				     bonus[count] = 34013
				  end    
			   else 
				  bonus[count] = 34013
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34061
			   end 
		   end 
       else
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   -- prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34011
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34012
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34013
				     end
				  else
				     bonus[count] = 34013
				  end    
			   else 
				  bonus[count] = 34013
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34011
			   end 
		   end
	   end
	end
end

-- VIP5以上，VIP8以下
if vip_level >= 5 and vip_level < 8 then
	-- 首次水晶十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	if pray_count == 0 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34015
			  end    
		   else 
			  bonus[count] = 34015
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第2次十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	elseif pray_count == 1 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34014
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34015
			  end    
		   else 
			  bonus[count] = 34015
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34014
		   end 
	   end
	-- 第3-5次十连抽
	-- 必定会出一个伙伴：S，SS
	-- 必定不出两个伙伴
	elseif pray_count > 1 and pray_count < 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34016
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34017
			  end    
		   else 
			  bonus[count] = 34017
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34016
		   end 
	   end
	-- 第6次十连抽
	-- 必定会出一个伙伴：SS
	-- 必定不出两个伙伴
	elseif pray_count == 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34017
			  end    
		   else 
			  bonus[count] = 34017
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第7次十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	elseif pray_count == 6 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34014
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34017
			  end    
		   else 
			  bonus[count] = 34017
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34014
		   end 
	   end
	-- 第8-10次十连抽
	-- 必定会出一个伙伴：S，SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count > 6 and pray_count < 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34018
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34019
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34020
			     end
			  else
			     bonus[count] = 34020
			  end    
		   else 
			  bonus[count] = 34020
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34018
		   end 
	   end
	-- 第11次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count == 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34019
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34020
			     end
			  else
			     bonus[count] = 34020
			  end    
		   else 
			  bonus[count] = 34020
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第12-15次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 10 and pray_count < 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34021
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34022
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34023
			     end
			  else
			     bonus[count] = 34023
			  end    
		   else 
			  bonus[count] = 34023
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34021
		   end 
	   end
	-- 第16次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count == 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34022
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34023
			     end
			  else
			     bonus[count] = 34023
			  end    
		   else 
			  bonus[count] = 34023
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第17-20次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 15 and pray_count < 20 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34021
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34022
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34023
			     end
			  else
			     bonus[count] = 34023
			  end    
		   else 
			  bonus[count] = 34023
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34021
		   end 
	   end
	-- 20次后十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴可能是S或SS（包括稀有）
	else
		if math.fmod(pray_count,5) == 0 then
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   -- prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34061
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34025
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34026
				     end
				  else
				     bonus[count] = 34026
				  end    
			   else 
				  bonus[count] = 34026
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34061
			   end 
		   end
		else
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   -- prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34024
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34025
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34026
				     end
				  else
				     bonus[count] = 34026
				  end    
			   else 
				  bonus[count] = 34026
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34024
			   end 
		   end			
		end
	end
end

-- VIP8以上，VIP12以下
if vip_level >= 8 and vip_level < 12 then
	-- 首次水晶十连抽
	-- 必定会出一个伙伴：SS
	-- 必定不出两个伙伴
	if pray_count == 0 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34027
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34028
			  end    
		   else 
			  bonus[count] = 34028
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34027
		   end 
	   end
	-- 第2次十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	elseif pray_count == 1 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34029
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34030
			  end    
		   else 
			  bonus[count] = 34030
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34029
		   end 
	   end
	-- 第3-5次十连抽
	-- 必定会出一个伙伴：S,SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count > 1 and pray_count < 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34031
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34032
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34033
			     end
			  else
			     bonus[count] = 34033
			  end    
		   else 
			  bonus[count] = 34033
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34031
		   end 
	   end
	-- 第6次十连抽
	-- 必定会出一个伙伴：SS
	-- 必定不出两个伙伴
	elseif pray_count == 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34033
			  end    
		   else 
			  bonus[count] = 34033
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第7次十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	elseif pray_count == 6 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34032
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34033
			  end    
		   else 
			  bonus[count] = 34033
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34032
		   end 
	   end
	-- 第8-10次十连抽
	-- 必定会出一个伙伴：S,SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count > 6 and pray_count < 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34034
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34035
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34036
			     end
			  else
			     bonus[count] = 34036
			  end    
		   else 
			  bonus[count] = 34036
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34034
		   end 
	   end
	-- 第11次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count == 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34035
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34036
			     end
			  else
			     bonus[count] = 34036
			  end    
		   else 
			  bonus[count] = 34036
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第12-15次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 10 and pray_count < 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34037
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34038
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34039
			     end
			  else
			     bonus[count] = 34039
			  end    
		   else 
			  bonus[count] = 34039
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34037
		   end 
	   end
	-- 第16次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count == 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34038
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34039
			     end
			  else
			     bonus[count] = 34039
			  end    
		   else 
			  bonus[count] = 34039
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第17-20次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 15 and pray_count < 20 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34037
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34038
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34039
			     end
			  else
			     bonus[count] = 34039
			  end    
		   else 
			  bonus[count] = 34039
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34037
		   end 
	   end
	-- 20次后十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴可能是S或SS（包括稀有）
	else
		if math.fmod(pray_count,5) == 0 then
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34061
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34041
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34042
				     end
				  else
				     bonus[count] = 34042
				  end    
			   else 
				  bonus[count] = 34042
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34061
			   end 
		   end
		else
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34040
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34041
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34042
				     end
				  else
				     bonus[count] = 34042
				  end    
			   else 
				  bonus[count] = 34042
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34040
			   end 
		   end
		end
	end
end

-- VIP12以上
if vip_level >= 12 then
	-- 首次水晶十连抽
	-- 必定会出一个伙伴：SS
	-- 必定不出两个伙伴
	if pray_count == 0 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34043
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34044
			  end    
		   else 
			  bonus[count] = 34044
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34043
		   end 
	   end
	-- 第2次十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	elseif pray_count == 1 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34045
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34046
			  end    
		   else 
			  bonus[count] = 34046
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34045
		   end 
	   end
	-- 第3-5次十连抽
	-- 必定会出一个伙伴：S,SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count > 1 and pray_count < 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34047
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34048
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34049
			     end
			  else
			     bonus[count] = 34049
			  end    
		   else 
			  bonus[count] = 34049
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34047
		   end 
	   end
	-- 第6次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count == 5 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34048
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34049
			     end
			  else
			     bonus[count] = 34049
			  end    
		   else 
			  bonus[count] = 34049
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
    -- 第7次十连抽
	-- 必定会出一个伙伴：S
	-- 必定不出两个伙伴
	elseif pray_count == 6 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34045
			     partner_count = partner_count + 1 
			  else
			     bonus[count] = 34049
			  end    
		   else 
			  bonus[count] = 34049
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34045
		   end 
	   end
	-- 第8-10次十连抽
	-- 必定会出一个伙伴：所有S，小几率产出稀有SS（1000，1044）
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count > 6 and pray_count < 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34050
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34051
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34052
			     end
			  else
			     bonus[count] = 34052
			  end    
		   else 
			  bonus[count] = 34052
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34050
		   end 
	   end
	-- 第11次十连抽
	-- 必定会出一个伙伴：所有S，小几率产出稀有SS（1000，1044）
	-- 可能会出两个伙伴：第2个伙伴必定是S
	elseif pray_count == 10 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34051
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34052
			     end
			  else
			     bonus[count] = 34052
			  end    
		   else 
			  bonus[count] = 34052
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第12-15次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 10 and pray_count < 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34053
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34054
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34055
			     end
			  else
			     bonus[count] = 34055
			  end    
		   else 
			  bonus[count] = 34055
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34053
		   end 
	   end
	-- 第16次十连抽
	-- 必定会出一个伙伴：SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count == 15 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34061
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34054
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34055
			     end
			  else
			     bonus[count] = 34055
			  end    
		   else 
			  bonus[count] = 34055
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34061
		   end 
	   end
	-- 第17-20次十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴必定是S
    elseif pray_count > 15 and pray_count < 20 then
	   for count = 1,10 do
		   prop1 = math.random(0,100)
		   -- prop2 = math.random(0,100) 
		   if prop1 <= 20 then
			  if partner_count == 0 then 
			     bonus[count] = 34053
			     partner_count = partner_count + 1
              elseif partner_count == 1 then
			     if prop1 <= 0.5 then
			        bonus[count] = 34054
			        partner_count = partner_count + 1
			     else 
			        bonus[count] = 34055
			     end
			  else
			     bonus[count] = 34055
			  end    
		   else 
			  bonus[count] = 34055
		   end
     
     	   if count == 10 and partner_count == 0 then
			  bonus[count] = 34053
		   end 
	   end
	-- 20次后十连抽
	-- 必定会出一个伙伴：所有S和所有SS
	-- 可能会出两个伙伴：第2个伙伴可能是S或SS（包括稀有）
	else
		if math.fmod(pray_count,5) == 0 then
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   -- prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34061
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34057
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34058
				     end
				  else
				     bonus[count] = 34058
				  end    
			   else 
				  bonus[count] = 34058
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34061
			   end 
		   end
		else
	       for count = 1,10 do
			   prop1 = math.random(0,100)
			   -- prop2 = math.random(0,100) 
			   if prop1 <= 20 then
				  if partner_count == 0 then 
				     bonus[count] = 34056
				     partner_count = partner_count + 1
	              elseif partner_count == 1 then
				     if prop1 <= 0.5 then
				        bonus[count] = 34057
				        partner_count = partner_count + 1
				     else 
				        bonus[count] = 34058
				     end
				  else
				     bonus[count] = 34058
				  end    
			   else 
				  bonus[count] = 34058
			   end
	     
	     	   if count == 10 and partner_count == 0 then
				  bonus[count] = 34056
			   end 
		   end
		end
	end
end

-- print(bonus[1],bonus[2],bonus[3],bonus[4],bonus[5],bonus[6],bonus[7],bonus[8],bonus[9],bonus[10])

return bonus

end

-----------------------------------------------------
-- 定向单抽
-- 必定不出：1025（竞技场）、1034（竞技场）
-----------------------------------------------------
function pray_3001(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local pray_value
local bonus = {}

-- 定向抽只对VIP9以上用户开放
-- 定向抽不存在免费抽卡，所以抽卡次数 = 累计消费

-- 首次定向单抽
-- 必定出1021或1049的魂石
if current_pay_times == 0 then
   pray_value = 35001
end 

-- 第二次定向单抽
-- 必定不出1021或1049的魂石
-- 可能会出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S、其他SS）
if current_pay_times == 1 then
   pray_value = 35002
end 

-- 无论免费/付费，第5次抽卡必出S/SS伙伴
-- 1. 如果累积付费抽卡次数少于5次，那么只会抽取到限定的S伙伴
-- 2. 如果累积付费抽卡次数多于5次，那么还会抽取到其他的S伙伴
-- 3. 如果累积付费抽卡次数多于10次，那么还会抽取到限定的SS伙伴（1000、1044）
-- 4. 如果累积付费抽卡次数多于20次，那么还会抽取到其他的SS伙伴（不包括竞技场限定伙伴）
if math.fmod(current_pay_times,5) == 0 and current_pay_times ~= 0 and current_pay_times ~= 1 then
   if current_pay_times < 5 then
      pray_value = 35003   	
   elseif current_pay_times >= 5 and current_pay_times < 10 then
   	  pray_value = 35004
   elseif current_pay_times >= 10 and current_pay_times < 20 then
   	  pray_value = 35005
   else
   	  pray_value = 35006
   end
end

-- 每第1次-第4次的抽卡情况
-- 1. 如果累积抽卡次数少于5次，那么只会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（限定S）
-- 2. 如果累积付费抽卡次数多于5次，那么还会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S）
-- 3. 如果累积付费抽卡次数多于10次，那么还会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S、限定SS）、伙伴（仅限S）
-- 4. 如果累积付费抽卡次数多于20次，那么还会产出：卖钱道具、经验药剂、技能药剂、绿色精炼材料、蓝色精炼材料、纹章材料、时空灰烬、伙伴魂石（其他S、其他SS）、伙伴（仅限S）
if math.fmod(current_pay_times,5) ~= 0 and current_pay_times ~= 0 and current_pay_times ~= 1 then
   if current_pay_times < 5 then   	
      pray_value = 35007
   elseif current_pay_times >= 5 and current_pay_times < 10 then
      pray_value = 35008
   elseif current_pay_times >= 10 and current_pay_times < 20 then
   	  pray_value = 35009
   else
   	  pray_value = 35010
   end
end

-- 定向单抽不区分VIP
bonus[1] = pray_value

-- print(bonus[1])

return bonus

end

-----------------------------------------------------
-- 定向十连
-- 必定不出：1025（竞技场）、1034（竞技场）
-----------------------------------------------------
function pray_3010(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local count
local bonus = {}

local partner_count = 0
local soul_count = 0
local pray_count = current_pay_times

local prop1
local prop2

math.randomseed(tostring(os.time()):reverse():sub(1, 6))

-- 首次定向十连
-- 必定会出1021或1049的魂石
-- 必定会出一个伙伴：1016、1046、1052其中之一
-- 必定不出两个伙伴
if pray_count == 0 then
   bonus[1] = 36001
   bonus[2] = 36001
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36002
		     partner_count = partner_count + 1 
		  else
		     bonus[count] = 36003
		  end    
	   else 
		  bonus[count] = 36003
	   end
     
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36002
	   end 
   end
-- 第2次定向十连
-- 必定会出1021或1049的魂石
-- 必定会出一个伙伴：1041
-- 必定不出两个伙伴
elseif pray_count == 1 then
   bonus[1] = 36004
   bonus[2] = 36004
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36005
		     partner_count = partner_count + 1 
		  else
		     bonus[count] = 36006
		  end    
	   else 
		  bonus[count] = 36006
	   end
    
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36005
	   end 
   end
-- 第3次定向十连
-- 必定会出1021或1049的魂石
-- 必定会出一个伙伴：所有S之一
-- 必定不出两个伙伴
elseif pray_count == 2 then
   bonus[1] = 36004
   bonus[2] = 36004
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36005
		     partner_count = partner_count + 1 
		  else
		     bonus[count] = 36006
		  end    
	   else 
		  bonus[count] = 36006
	   end
    
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36005
	   end 
   end
-- 第4次定向十连
-- 必定会出1048的整卡或20个碎片
-- 必定不出两个伙伴
elseif pray_count == 3 then
   bonus[1] = 36007
   bonus[2] = 36007
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   if prop1 <= 50 then
		  if partner_count == 0 then 
		     bonus[count] = 36005
		     partner_count = partner_count + 1 
		  else
		     bonus[count] = 36006
		  end    
	   else 
		  bonus[count] = 36006
	   end
    
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36005
	   end 
   end
-- 第5次定向十连
-- 必定会出1048的整卡
-- 必定会出一个伙伴：所有S，小几率产出限定SS（1021，1049）
-- 必定不出两个伙伴
elseif pray_count == 4 then
   bonus[1] = 36007
   bonus[2] = 36007
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36008
		     partner_count = partner_count + 1 
		  else
		     bonus[count] = 36009
		  end    
	   else 
		  bonus[count] = 36009
	   end
    
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36008
	   end 
   end
-- 第6次定向十连
-- 必定会出1021或1049的魂石
-- 必定会出一个伙伴：所有S，小几率产出限定SS（1021，1049）
-- 必定不出两个伙伴
elseif pray_count == 5 then
   bonus[1] = 36007
   bonus[2] = 36007
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36008
		     partner_count = partner_count + 1 
		  else
		     bonus[count] = 36009
		  end    
	   else 
		  bonus[count] = 36009
	   end
    
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36008
	   end 
   end
-- 前12次定向十连
-- 必定会出1021或1049的魂石
-- 必定会出一个伙伴：所有S和所有SS（不包括1025、1034）
-- 可能会出两个伙伴：第2个伙伴必定是S
elseif pray_count > 5 and pray_count <= 12 then
   bonus[1] = 36010
   bonus[2] = 36010
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   prop2 = math.random(0,100) 
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36011
		     partner_count = partner_count + 1
          elseif partner_count == 1 then
		     if prop1 <= 0.5 then
		        bonus[count] = 36012
		        partner_count = partner_count + 1
		     else 
		        bonus[count] = 36013
		     end
		  else
		     bonus[count] = 36013
		  end    
	   else 
		  bonus[count] = 36013
	   end
    
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36011
	   end 
   end
-- 12次后定向十连
-- 必定会出1021或1049的魂石
-- 必定会出一个伙伴：所有S和所有SS（不包括1025、1034）
-- 可能会出两个伙伴：第2个伙伴可能是S或SS（包括稀有）
else
   bonus[1] = 36014
   bonus[2] = 36014
   for count = 3,10 do
	   prop1 = math.random(0,100)
	   prop2 = math.random(0,100) 
	   if prop1 <= 20 then
		  if partner_count == 0 then 
		     bonus[count] = 36015
		     partner_count = partner_count + 1
          elseif partner_count == 1 then
		     if prop1 <= 0.5 then
		        bonus[count] = 36016
		        partner_count = partner_count + 1
		     else 
		        bonus[count] = 36017
		     end
		  else
		     bonus[count] = 36017
		  end    
	   else 
		  bonus[count] = 36017
	   end
     
   	   if count == 10 and partner_count == 0 then
		  bonus[count] = 36015
	   end 
   end
end

-- print(bonus[1],bonus[2],bonus[3],bonus[4],bonus[5],bonus[6],bonus[7],bonus[8],bonus[9],bonus[10])

-- 定向十连不区分VIP
return bonus

end

-----------------------------------------------------
-- 限时伙伴：单抽
-- 有几率出：1031，1048，1050
-----------------------------------------------------
function pray_10001(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local pray_value
local bonus = {}

-- 无论免费/付费，第10次抽卡必出S/SS伙伴
if math.fmod(current_pay_times,10) == 0 and current_pay_times ~= 0 and current_pay_times ~= 1 then
   if current_pay_times < 5 then
      pray_value = 37001   	
   elseif current_pay_times >= 5 and current_pay_times < 10 then
   	  pray_value = 37002
   elseif current_pay_times >= 10 and current_pay_times < 20 then
   	  pray_value = 37003
   else
   	  pray_value = 37004
   end
else
	pray_value = 37005
end

-- 每第1次-第9次的抽卡情况
if math.fmod(current_pay_times,10) ~= 0 and current_pay_times ~= 0 and current_pay_times ~= 1 then
   if current_pay_times < 5 then   	
      pray_value = 37005
   elseif current_pay_times >= 5 and current_pay_times < 10 then
      pray_value = 37006
   elseif current_pay_times >= 10 and current_pay_times < 20 then
   	  pray_value = 37007
   else
   	  pray_value = 37008
   end
end

-- 限时伙伴单抽不区分VIP
bonus[1] = pray_value

-- print(bonus[1])

return bonus

end

-----------------------------------------------------
-- 限时伙伴：十连
-- 有几率出：1031，1048，1050
-----------------------------------------------------
function pray_10010(pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level)

local count
local bonus = {}

   for count = 1,10 do    
		  bonus[count] = 38001
   end

-- print(bonus[1],bonus[2],bonus[3],bonus[4],bonus[5],bonus[6],bonus[7],bonus[8],bonus[9],bonus[10])

-- 限时伙伴十连不区分VIP
return bonus

end


--[[
function test()
-- pray_type, is_free, vip_level, is_first_pray, current_free_times, current_pay_times, team_level
local count

for count =  0, 10 do 
   pray_2010(0, 0, 5, 0, 0, count, 31)
end

end

print(test())

]]--

