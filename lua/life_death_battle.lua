----------------更新历史------------------------------
--日期：2015-07-02
--作者：xipxop
--修改：重建脚本

--日期：2015-07-08
--作者：xipxop
--修改：增加参数传入，根据战力获取每层守护者

--日期：2015-09-11
--作者：xipxop
--修改：新增守护者随机邮件的函数

----------------随机邮件------------------------------
function guard_mail(team_level)
	
	-- 根据战队等级，确定是否会给予奖励邮件
	-- 玩家每天首次登录游戏时，会有几率收到奖励邮件
	-- 返回mail id，如果返回0，意味着不会发奖励邮件

	local prop
	local mail

	math.randomseed(tostring(os.time()):reverse():sub(1, 6)) 

	if team_level >= 17 and team_level <= 20 then
	   prop = math.random(0,100)
	   if prop <= 20 then
	      mail = 50001
	   else
	   	  mail = 0
	   end
	elseif team_level > 20 and team_level <= 24 then
	   prop = math.random(0,100)
	   if prop <= 25 then
	      mail = 50002
	   else
	   	  mail = 0
	   end
	elseif team_level > 24 and team_level <= 30 then
	   prop = math.random(0,100)
	   if prop <= 30 then
	      mail = 50003
	   else
	   	  mail = 0
	   end
	elseif team_level > 31 and team_level <= 35 then
	   prop = math.random(0,100)
	   if prop <= 35 then
	      mail = 50004
	   else
	   	  mail = 0
	   end
	elseif team_level > 36 and team_level <= 40 then  
	   prop = math.random(0,100)
	   if prop <= 40 then
	      mail = 50005
	   else
	   	  mail = 0
	   end
	else
	   prop = math.random(0,100)
	   if prop <= 50 then
	      mail = 50006
	   else
	   	  mail = 0
	   end
	end

	return mail

end


----------------算法说明------------------------------
--1. 挑战者战力越高，其该层所能匹配到的对手的竞技场排名就越高（假定：竞技场排名越高 = 战力越高）
--2. 挑战者当前挑战的层数越高，其该层所能匹配到的对手的竞技场排名就越高
--3. 挑战者当前竞技场排名越高，其该层所能匹配到的对手的竞技场排名就越高
--4. 设定战力标准线，低于战力线时，匹配机器人，高于战力线时，匹配竞技场中的对应排名的敌人
-----4.1 不足1500，那么就直接适配机器人，每层的守护者就固化为Robot_Team的编号，从Team 1开始配置
-----4.2 不足2000，那么就直接适配机器人，每层的守护者就固化为Robot_Team的编号，从Team 6开始配置
-----4.3 不足2900，那么就直接适配机器人，每层的守护者就固化为Robot_Team的编号，从Team 11开始配置
-----4.4 超过2900，那么看排名，将竞技场分为以下档次，根据战力匹配对应档次
---------档次 1：9500 - 10000
---------档次 2：9000 - 9500
---------档次 3：8500 - 9000
---------档次 4：8000 - 8500
---------档次 5：7500 - 8000
---------档次 6：7000 - 7500
---------档次 7：6500 - 7000
---------档次 8：6000 - 6500
---------档次 9：5500 - 6000
---------档次10：5000 - 5500
---------档次11：4500 - 5000
---------档次12：4000 - 4500
---------档次13：3500 - 4000
---------档次14：3000 - 3500
---------档次15：2500 - 3000
---------档次16：2000 - 2500
---------档次17：1500 - 2000
---------档次18：1000 - 1500
---------档次19：500  - 1000
---------档次20：400  - 500
---------档次21：300  - 400
---------档次22：200  - 300
---------档次23：100  - 200
---------档次24：50   - 100
---------档次25：40   - 50
---------档次26：30   - 40
---------档次27：20   - 30
---------档次28：10   - 20
---------档次29：1    - 10

----------------控制参数------------------------------
ABILITY_LEVEL_1 = 1500		-- 竞技场机器人的战力档，对应Team 1 - 5
ABILITY_LEVEL_2 = 2000		-- 竞技场机器人的战力档，对应Team 6 - 10
ABILITY_LEVEL_3 = 2900		-- 竞技场机器人的战力档，对应Team11 - 16

math.randomseed(tostring(os.time()):reverse():sub(1, 6))

ENEMY_LEVEL_1 = math.random(9500, 10000)
ENEMY_LEVEL_2 = math.random(9000, 9499)
ENEMY_LEVEL_3 = math.random(8500, 8999)
ENEMY_LEVEL_4 = math.random(8000, 8499)
ENEMY_LEVEL_5 = math.random(7500, 7999)
ENEMY_LEVEL_6 = math.random(7000, 7499)
ENEMY_LEVEL_7 = math.random(6500, 6999)
ENEMY_LEVEL_8 = math.random(6000, 6499)
ENEMY_LEVEL_9 = math.random(5500, 5999)
ENEMY_LEVEL_10 = math.random(5000, 5499)
ENEMY_LEVEL_11 = math.random(4500, 4999)
ENEMY_LEVEL_12 = math.random(4000, 4499)
ENEMY_LEVEL_13 = math.random(3500, 3999)
ENEMY_LEVEL_14 = math.random(3000, 3499)
ENEMY_LEVEL_15 = math.random(2500, 2999)
ENEMY_LEVEL_16 = math.random(2000, 2499)
ENEMY_LEVEL_17 = math.random(1500, 1999)
ENEMY_LEVEL_18 = math.random(1000, 1499)
ENEMY_LEVEL_19 = math.random(500, 999)
ENEMY_LEVEL_20 = math.random(400, 499)
ENEMY_LEVEL_21 = math.random(300, 399)
ENEMY_LEVEL_22 = math.random(200, 299)
ENEMY_LEVEL_23 = math.random(100, 199)
ENEMY_LEVEL_24 = math.random(50, 99)
ENEMY_LEVEL_25 = math.random(40, 49)
ENEMY_LEVEL_26 = math.random(30, 39)
ENEMY_LEVEL_27 = math.random(20, 29)
ENEMY_LEVEL_28 = math.random(10, 19)
ENEMY_LEVEL_29 = math.random(1, 9)

-- 主逻辑 --
function life_death_battle(player_rank,player_force,secne_num,last_num)

	local rank = tonumber(player_rank)     -- 战队排名 
	local ability = tonumber(player_force) -- 挑战者最高5人战力 
	local secne = tonumber(secne_num)      -- 当前审判之门的层数
	local num = tonumber(last_num)         -- 竞技场最后排名位置

	local d_rank
	local robot_index
   
    if ability < ABILITY_LEVEL_1 then
       d_rank = 0 
       robot_index = 20 - (secne-1) * 2
    elseif ability >= ABILITY_LEVEL_1 and ability < ABILITY_LEVEL_2 then
       d_rank = 0
       robot_index = 20 - (secne-1) * 2
       -- robot_index = math.max(math.floor(14 - (secne-1) * 1.5), 1)
    elseif ability >= ABILITY_LEVEL_2 and ability < ABILITY_LEVEL_3 then
       d_rank = 0
       robot_index = 20 - (secne-1) * 2
       -- robot_index = 11 - secne
    else
       robot_index = 0
       if secne == 1 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_1
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_1
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_1
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_2
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_2
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_14
          else d_rank = ENEMY_LEVEL_20
          end
       elseif secne == 2 then
       	  if rank > 10000 then d_rank = ENEMY_LEVEL_2
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_2
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_2
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_15
       	  else d_rank = ENEMY_LEVEL_21
       	  end
       elseif secne == 3 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_3
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_16
          else d_rank = ENEMY_LEVEL_22
          end
       elseif secne == 4 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_4
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_22
          else d_rank = ENEMY_LEVEL_23
          end
       elseif secne == 5 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_5
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_22
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_23
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_24
          else d_rank = ENEMY_LEVEL_24
          end
       elseif secne == 6 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_6
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_22
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_23
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_25
          else d_rank = ENEMY_LEVEL_25
          end
       elseif secne == 7 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_7
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_22
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_23
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_26
          else d_rank = ENEMY_LEVEL_26
          end
       elseif secne == 8 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_8
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_22
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_23
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_27
          else d_rank = ENEMY_LEVEL_27
          end
       elseif secne == 9 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_9
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_22
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_23
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_28
          else d_rank = ENEMY_LEVEL_28
          end
       elseif secne == 10 then
          if rank > 10000 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 10000 and rank > 9500 then d_rank = ENEMY_LEVEL_10
          elseif rank <= 9500 and rank > 9000 then d_rank = ENEMY_LEVEL_11
          elseif rank <= 9000 and rank > 8500 then d_rank = ENEMY_LEVEL_12
          elseif rank <= 8500 and rank > 8000 then d_rank = ENEMY_LEVEL_13
          elseif rank <= 8000 and rank > 7500 then d_rank = ENEMY_LEVEL_14
          elseif rank <= 7500 and rank > 7000 then d_rank = ENEMY_LEVEL_15
          elseif rank <= 7000 and rank > 6500 then d_rank = ENEMY_LEVEL_16
          elseif rank <= 6500 and rank > 6000 then d_rank = ENEMY_LEVEL_17
          elseif rank <= 6000 and rank > 5500 then d_rank = ENEMY_LEVEL_18
          elseif rank <= 5500 and rank > 5000 then d_rank = ENEMY_LEVEL_19
          elseif rank <= 5000 and rank > 4500 then d_rank = ENEMY_LEVEL_20
          elseif rank <= 4500 and rank > 4000 then d_rank = ENEMY_LEVEL_21
          elseif rank <= 4000 and rank > 3500 then d_rank = ENEMY_LEVEL_22
          elseif rank <= 3500 and rank > 3000 then d_rank = ENEMY_LEVEL_23
          elseif rank <= 3000 and rank > 2500 then d_rank = ENEMY_LEVEL_24
          elseif rank <= 2500 and rank > 2000 then d_rank = ENEMY_LEVEL_25
          elseif rank <= 2000 and rank > 1500 then d_rank = ENEMY_LEVEL_26
          elseif rank <= 1500 and rank > 1000 then d_rank = ENEMY_LEVEL_27
          elseif rank <= 1000 and rank > 500 then d_rank = ENEMY_LEVEL_28
          elseif rank <= 500 and rank > 400 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 400 and rank > 300 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 300 and rank > 200 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 200 and rank > 100 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 100 and rank > 50 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 50 and rank > 40 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 40 and rank > 30 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 30 and rank > 20 then d_rank = ENEMY_LEVEL_29
          elseif rank <= 20 and rank > 10 then d_rank = ENEMY_LEVEL_29
          else
             d_rank = ENEMY_LEVEL_29
             if d_rank == 1  then
                d_rank = 2
             end
          end
       else d_rank = 1
       end
    end	

	  return d_rank, robot_index

end

-- print(life_death_battle(10008,1300,1,10000))
-- 战队排名 
-- 挑战者最高5人战力 
-- 当前审判之门的层数
-- 竞技场最后排名位置