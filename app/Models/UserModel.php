<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model{
    protected $table              = 'users';
    protected $returnType         = 'array';
    protected $allowedFields      = ['id','username', 'email','mobile','status','password','name','reg_status','reg_code',
                                     'last_login','last_accessed_ip','logins','ugroup_id','profile_picture','presetcode'
                                    ];
                                    
    
    protected $useTimestamps      = true;
    protected $createdField       = 'created';
    protected $updatedField       = 'modified';
    //protected $beforeInsert       = ['beforeInsert'];
    //protected $beforeUpdate       = ['beforeUpdate'];

    
    /*
    protected  function beforeInsert(array  $data){
        $data= $this->passwordHash($data);
        return $data;
    }

    protected  function beforeUpdate(array  $data){
        $data= $this->passwordHash($data);
        return $data;
    }
    protected  function passwordHash(array  $data){
        if (isset($data['data']['password'])){
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }

        return $data;
    }
    */
    
    
    
    
    
    


}