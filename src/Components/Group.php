<?php
namespace Woldy\ddsdk\Components;
use Cache;
use Httpful\Request;
use Woldy\ddsdk\Components\Group;
use Log;
class group{
    /**
     * 获取所有部门
     * @Author   Woldy
     * @DateTime 2016-08-31T10:36:26+0800
     * @param    [type]                   $ACCESS_TOKEN [description]
     * @param    boolean                  $refresh      [description]
     * @return   [type]                                 [description]
     */
	public static function getAllGroups($ACCESS_TOKEN,$refresh=false){
            $groups=Cache::get('all_groups');
            if(empty($allgroup) || $refresh){
                $param=http_build_query(
                    array(
                        'access_token'=>$ACCESS_TOKEN
                    )
                );
            
                $response = Request::get('https://oapi.dingtalk.com/department/list?'.$param)->send();
                if ($response->hasErrors()){
                    var_dump($response);
                    exit;
                }
                if ($response->body->errcode != 0){
                    var_dump($response->body);
                    exit;
                }
                $allgroups = $response->body->department;  
                $groups=[];
                foreach ($allgroups as $group) {
                    $groups[$group->id]=json_decode(json_encode($group),true);
                    $groups[$group->id]['tmpid']=$group->id;
                    $groups[$group->id]['parent_ids']=[];
                    $groups[$group->id]['name_part']=[];
                    $groups[$group->id]['fullname']='';
                }


                $cover_all=false;
                while(!$cover_all){
                    $cover_all=true;
                    foreach ($groups as $key => $value) {
                        if($groups[$key]['tmpid']>1){
                            $cover_all=false;
                            array_unshift($groups[$key]['parent_ids'], $groups[$key]['tmpid']);
                            array_unshift($groups[$key]['name_part'], $groups[$groups[$key]['tmpid']]['name']);
                            $groups[$key]['tmpid']=$groups[$groups[$key]['tmpid']]['parentid'];
                        }else if($groups[$key]['tmpid']==1){
                            array_unshift($groups[$key]['parent_ids'], 1);
                            array_unshift($groups[$key]['name_part'], $groups[1]['name']);
                            $groups[$key]['fullname']=implode('-',$groups[$key]['name_part']);
                            $groups[$key]['tmpid']=0;
                        }
                    }
                }

                Cache::put('groups', $groups,200);  
                            
            }

            return  $groups;
	}




    /**
     *  
     * @Author   根据部门名称获取id
     * @DateTime 2016-09-02T10:35:19+0800
     * @param    [type]                   $groupname    [description]
     * @param    [type]                   $ACCESS_TOKEN [description]
     * @param    boolean                  $refresh      [description]
     * @return   [type]                                 [description]
     */
    public static function getGroupByName($name,$ACCESS_TOKEN,$refresh=false){
            $group=Cache::get('group_name_'.$name);
            
            if(empty($group) || $refresh){
                $groups=self::getAllGroups($ACCESS_TOKEN,$refresh);
                foreach ($groups as $group) {
                    if($group['fullname']==$name){
                        return $name;
                    }
                }
           }else{
                return $group;
           }
       

            while (count($namepart)>0) {
                # code...
            }
            $namepart=explode('-',$name);
            $namepart=

            // var_dump($namepart);
            // var_dump($parentid);
            $add=self::createGroup($namepart[0],$parentid,$ACCESS_TOKEN);
            if($add->errcode==0){
                echo 'add group: '.self::getGroupById($parentid,$ACCESS_TOKEN)['fullname'].'-'.$namepart[0]."\n";
                Log::info("ding|group_add|".self::getGroupById($parentid,$ACCESS_TOKEN)['fullname'].'-'.$namepart[0]);
                return self::getGroupByName($namepart,$ACCESS_TOKEN,$parentid,true);
            }else{
                echo 'can\'t  add department: ';
                //var_dump($parentid);
                var_dump(self::getGroupById($parentid,$ACCESS_TOKEN)['fullname']);
                var_dump($namepart);       
                var_dump($add);        
            }

     
            
    }

 
    public static function createGroup($name,$parentid,$ACCESS_TOKEN){
            $param=array(
                'access_token' =>$ACCESS_TOKEN, 
                'name'=>$name,
                'parentid'=>$parentid,
            );

            $response = Request::post('https://oapi.dingtalk.com/department/create?access_token='.$ACCESS_TOKEN)
                ->body(json_encode($param))
                ->sendsJson()
                ->send();
            if ($response->hasErrors()){
                // var_dump($response);
                // exit;
            }
            if ($response->body->errcode != 0){
                // var_dump($response->body);
                // exit;
            }

            return $response->body;
    }


    /**
     * 根据ID获取部门信息
     * @Author   Woldy
     * @DateTime 2016-08-31T10:36:38+0800
     * @param    [type]                   $groupid      [description]
     * @param    [type]                   $ACCESS_TOKEN [description]
     * @param    boolean                  $refresh      [description]
     * @return   [type]                                 [description]
     */
    public static function getGroupById($groupid,$ACCESS_TOKEN,$sub=true,$refresh=false){
        $group=Cache::get('group_'.$groupid);
        if(empty($group) || $refresh){
            $groups=self::getAllGroups($ACCESS_TOKEN,$refresh);
            $groups=json_decode(json_encode($groups),TRUE);
            $groupinfo='';
            foreach ($groups as $group) {
                if($group['id']==$groupid){
                    break;
                }
            }

            if($sub){
                $group['sub_groups']=self::getSubGroups($groupid,$ACCESS_TOKEN,1,$refresh);
            }
            Cache::put('group_'.$groupid, $group,300);  
        }
        return $group;
    }


    /**
     * 获取子部门信息
     * @Author   Woldy
     * @DateTime 2016-08-31T10:37:03+0800
     * @param    [type]                   $groupid      [description]
     * @param    [type]                   $ACCESS_TOKEN [description]
     * @param    boolean                  $refresh      [description]
     * @return   [type]                                 [description]
     */
    public static function getSubGroups($groupid,$ACCESS_TOKEN,$deep=1,$refresh=false){
        $groups=self::getAllGroups($ACCESS_TOKEN,$refresh);
        foreach ($groups as $group) {
            if(!isset($group['parentid'])) $group['parentid']=0;
            if($group['parentid']==$groupid){
                $group['top']=$deep;
                array_push($subgroups,$group);
                if($deep>0){
                    $subagain=self::getSubGroups($group['id'],$ACCESS_TOKEN,$deep,$refresh);
                    $subgroups=array_merge($subgroups,$subagain);
                }

            }
        }

        return $subgroups;
    }
    

 
    public static function getGroupUsers($groupid,$ACCESS_TOKEN,$refresh=false){
        $groupusers=Cache::get('group_users_'.$groupid);
        if(empty($groupusers) || $refresh){
            $param=http_build_query(
                array(
                    'access_token'=>$ACCESS_TOKEN,
                    'department_id'=>$groupid
                )
            );
            $response = Request::get('https://oapi.dingtalk.com/user/list?'.$param)->send();
            if ($response->hasErrors()){
                var_dump($response);
                exit;
            }
            if ($response->body->errcode != 0){
                var_dump($response->body);
                exit;
            }
            $groupusers = $response->body->userlist;
            Cache::put('group_users_'.$groupid,$groupusers,30);  
        }            
        return  $groupusers;
    }

    public static function delGroup($groupid,$ACCESS_TOKEN){
            $param=http_build_query(
                array(
                    'access_token'=>$ACCESS_TOKEN,
                    'id'=>$groupid
                )
            );
            $response = Request::get('https://oapi.dingtalk.com/department/delete?'.$param)->send();
            if ($response->hasErrors()){
                var_dump($response);
                exit;
            }
            if ($response->body->errcode != 0){
                var_dump($response->body);
                exit;
            }
            $result = $response->body;            
            return  $result;
    }

    /**
     * 更新部门
     * @Author   woldy
     * @DateTime 2016-09-29T22:51:00+0800
     * @param    [type]                   $group        [description]
     * @param    [type]                   $ACCESS_TOKEN [description]
     * @return   [type]                                 [description]
     */
    public static function updateGroup($group,$ACCESS_TOKEN){
            $response = Request::post('https://oapi.dingtalk.com/department/update?access_token='.$ACCESS_TOKEN)
                ->body(json_encode($group))
                ->sendsJson()
                ->send();
            if ($response->hasErrors()){
                var_dump($response);
                exit;
            }
            if ($response->body->errcode != 0){
                var_dump($response->body);
                exit;
            }
            return $response->body;
    }
 
}