----------------更新历史------------------------------
--日期：2015-10-19
--作者：xipxop
--修改：创建脚本

-----------------------------------------------------

--配对规则
--1、周一：根据报名顺序，第一名和第二名、第三和第四、第五和第六、第七和第八，若为单数，则最后一名和倒数第二名进行匹配。
--2、周二：根据报名顺序进行配对，单双数进行，第一和第三、第二和第四、第五和第七、第六和第八，若最后剩下两个单数，则双方进行配对，若仅剩下一个，则与倒数第二个进行配对。
--3、周三：根据公会总战力进行排序，1VS2、3V4、5V6依次进行，若有单数，则N VS N-1的队伍
--4、周四：根据公会总战力进行排序，1V3、2V4、依次进行，若有2个，则互相VS，若为单数，则 VS N-1的队伍
--5、周五：根据积分进行排序，1V2、3V4、4V5，依次进行，若有若有单数，则VS N-1的队伍
--N=当前排名

function league_battle_pvp_matching(sequence,week_day)
-- sequence：数组，分别对应公会ID、报名顺序，当前积分、公会总战力
-- week_day：周几

  local count = 0
  local a = {}

  -- 数据初始化
  for key,value in pairs(sequence) do
      count = count + 1
      a[key + 1] = {}
      for m, v  in pairs(value) do
          a[key + 1][m] = v
      end
  end
  
  local number = math.ceil(count/4)

  -- 周一：根据报名顺序，第一名和第二名、第三和第四、第五和第六、第七和第八，若为单数，则最后一名和倒数第二名进行匹配
  -- 输出：12345
  -- 规则：报名顺序的值越小，排序越靠前
  if week_day == 1 then
     table.sort(a, function(m, n)
     	return m["sequence"] < n["sequence"]
     end)
  end

  -- 周二：根据报名顺序进行配对，单双数进行，第一和第三、第二和第四、第五和第七、第六和第八，若最后剩下单数，则与倒数第二个进行配对
  -- 输出：13245768，1324576，132456，13245
  -- 规则：先根据报名顺序排序；然后，每四个一组，组内i和i+2配对；如果为组内成员数量=1，排在最后，如果组内成员数量=2，按报名顺序从小到大排在最后，如果组内成员数量=3，先i和i+2配对，剩余的那个在最后
  if week_day == 2 then
     -- 第一步：根据报名顺序排序
     table.sort(a, function(m, n)
     	return m["sequence"] < n["sequence"]
     end)
     
     local a1Temp = a[1]
     
     for k = 2, count do
     	a[k - 1] = a[k]
     end
     a[count] = a1Temp
     
--     local returnArr = {}
--     
--     for i = 1, number do
--     
--     	local startIndex = 1+ (i - 1) * 4
--		
--		for k = startIndex,      
--     end
	
     -- 第二步：每四个一组，组内i和i+2配对（2号位和3号位互换位置即可）
     -- 如果为组内成员数量 < 3，不用调整排序
     -- 如果组内成员数量 >= 3，则23换位
--     local i
     -- 判断组内是不是超过三个成员
     -- 是，则23换位
--     if math.fmod(count) >= 3 then 
--        for i = 0, number do 
--            a[i*4+2]["league_id"], a[i*4+3]["league_id"] =  a[i*4+3]["league_id"], a[i*4+2]["league_id"]
--        end
--     -- 否，则只进行到上一组的23换位（上一组必定是4个成员）
--     -- 先判断是否能返回上一组（number至少=1），存在一共只有两队报名的情况，这种情况下没有操作的必要
--     else
--        if number >= 1 then
--           for i = 0, number-1 do 
--               a[i*4+2]["league_id"], a[i*4+3]["league_id"] =  a[i*4+3]["league_id"], a[i*4+2]["league_id"]
--           end
--        end
--     end

			
  end
  

  -- 周三：根据公会总战力顺序，第一名和第二名、第三和第四、第五和第六、第七和第八，若为单数，则最后一名和倒数第二名进行匹配
  -- 规则：公会战力越高，排序越靠前
  if week_day == 3 then
     table.sort(a, function(m, n)
     return m["force"] > n["force"]
     end)
  end

  -- 周四：根据公会总战力顺序进行配对，单双数进行，第一和第三、第二和第四、第五和第七、第六和第八，若最后剩下单数，则与倒数第二个进行配对
  -- 规则：先根据公会战力排序；然后，每四个一组，组内i和i+2配对；如果为组内成员数量=1，排在最后，如果组内成员数量=2，按报名顺序从小到大排在最后，如果组内成员数量=3，先i和i+2配对，剩余的那个在最后
  if week_day == 4 then
     table.sort(a, function(m, n)
     	return m["force"] > n["force"]
     end)
          
          
     for i = 1, number do
     	
     	local startIndex = 1+ (i - 1) * 4
		
		if a[startIndex + 1] and a[startIndex + 2] then
			local temp = a[startIndex + 1]
			a[startIndex + 1] = a[startIndex + 2]
			a[startIndex + 2] = temp
		end     
     end

     -- 第二步：每四个一组，组内i和i+2配对（2号位和3号位互换位置即可）
     -- 如果为组内成员数量 < 3，不用调整排序
     -- 如果组内成员数量 >= 3，则23换位
     --local j
--     -- 判断组内是不是超过三个成员
--     -- 是，则23换位
--     if math.fmod(count) >= 3 then 
--        for j = 0, number do 
--            a[j*4+2]["league_id"], a[j*4+3]["league_id"] =  a[j*4+3]["league_id"], a[j*4+2]["league_id"]
--        end
--     -- 否，则只进行到上一组的23换位（上一组必定是4个成员）
--     -- 先判断是否能返回上一组（number至少=1），存在一共只有两队报名的情况，这种情况下没有操作的必要
--     else
--        if number >= 1 then
--           for j = 0, number-1 do 
--               a[j*4+2]["league_id"], a[j*4+3]["league_id"] =  a[j*4+3]["league_id"], a[j*4+2]["league_id"]
--           end
--        end
--     end
  end

  -- 周五：根据积分顺序，第一名和第二名、第三和第四、第五和第六、第七和第八，若为单数，则最后一名和倒数第二名进行匹配
  -- 规则：积分越高，排序越靠前
  if week_day == 5 then
     table.sort(a, function(m, n)
     return m["point"] > n["point"]
     end)
  end
  
  -- 定义数组，用于返回排序后的公会ID
  local sorted_guile_id = {}

  for k, v in pairs(a) do
    table.insert(sorted_guile_id, v["league_id"])
  end

  return sorted_guile_id

end