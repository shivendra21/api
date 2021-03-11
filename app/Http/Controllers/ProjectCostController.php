<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Project;
use App\Models\Cost;
use App\Models\CostType;

class ProjectCostController extends Controller
{
    public function calculateCostProject(Request $resquest){
    	$param = $resquest->input();
        $client_id = (isset($param['client_id'])) ? $param['client_id'] : []; 
        
        $data = [];
        if(!empty($client_id)){
            $result = Client::whereIn('id',$client_id)->get()->toArray();   
        }else{
            $result = Client::get()->toArray();
        }
    	
        foreach ($result as $key => $value) {
            $data[] = array(
                'id' => $value['id'],
                'amount' => $this->getClientAmount($value['id']),
                'type' => 'client',
                'name' => $value['name'],
                'children' => $this->projectData($value['id'])
            );
    	}
    	return response()->json([
            'data' => $data,
        ]);
    	
    }
    public function projectData($id){
        $data = [];
        $prjects = Project::where('client_id',$id)->get()->toArray();
        foreach ($prjects as $key => $prject) {
            $data[] = array(
                'id' => $prject['id'],
                'amount' => $this->getAmountProject($prject['id']),
                'type' => 'project',
                'name' => $prject['title'],
                'children' => $this->costData($prject['id'])
            ); 
        }
        return $data;
    }

    public function costData($id){
        $data = [];
        $costTypes = Cost::where('project_id',$id)->orderBy('cost_type_id','ASC')->get()->toArray();
        foreach ($costTypes as $key => $costType) {
          $store = $this->getTreeArray(CostType::where('id',$costType['cost_type_id'])->get()->toArray(),null,$id); 
          if(!empty($store)){
              $data[] = $store;
            }
        }
        return $data;
    }
    public function getTreeArray($tree, $root = null,$project=''){
                $return = array();
                
            foreach($tree as $child => $row) {
               if($row['parent_id'] == $root) {
                    unset($tree[$child]);
                   
                    $return[] = array(
                        'id' => $row['id'],
                        'type' => 'cost',
                        'name' => $row['name'],
                        'amount' => $this->getAmount($project,$row['id']),
                        'children' => $this->getTreeArray($this->get_parent_id($row['id']), $row['id'],$project)
                    );
                }
            }
            return empty($return) ? [] : $return;

    }
    function getAmount($p,$c) {
        $amount = Cost::where('project_id',$p)->where('cost_type_id',$c)->first();
        return $amount->amount;
    }
    public function getAmountProject($id){
        $amount=0;
        $costTypes = Cost::where('project_id',$id)->orderBy('cost_type_id','ASC')->get()->toArray();
        foreach ($costTypes as $key => $costType) {
          $store = $this->getTreeArray(CostType::where('id',$costType['cost_type_id'])->get()->toArray(),null,$id); 
          if(!empty($store)){
              $amount +=$costType['amount'];
            }
        }
        return $amount;
    }
    public function getClientAmount($id){
        $amount=0;
        $prjects = Project::where('client_id',$id)->get()->toArray();
        foreach ($prjects as $key => $prject) {
          $amount += $this->getAmountProject($prject['id']);
        }
        return $amount;
        
    }
    public function get_parent_id($parent_id){
    	return CostType::where('parent_id',$parent_id)->get()->toArray();
    }
    
}
