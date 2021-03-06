<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\PlaceDetails;
use App\Traits\Algoritm;

class ClientController extends Controller
{
    use Algoritm;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get Data
        $get_place = PlaceDetails::orderBy("pd_name", "asc")->get();

        // Mapping
        $data['lokasi'] = $get_place;

        return view('contentClient.client.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function calculate(Request $request){
        $process = ($this->algoritm_index($request));
        $group = array();
        $place = array();
        $rute = array();
        $angkot = '';
        $index = 0;

        $return_data = array();
        $return_data['maps_detail'] = array();
        $get_place = PlaceDetails::orderBy("pd_name", "asc")->get();

        foreach ($process['get_Distance'] as $data){
            $getPlace = PlaceDetails::where('pd_name', $data)->get()->toArray();
            foreach ($getPlace as $listData){
                // $place[] =  $listData;
                
                if (!empty($process['transit']['place_name'])){
                    foreach ($process['transit']['place_name'] as $transit){
                        if ($data != $transit){
                            $trayek = $process['transit']['angkot'][$index];
                            $status = '';
                        }else{
                            $index++;
                            if (isset($process['transit']['angkot'][$index])){
                                $trayek = $process['transit']['angkot'][$index];
                                $status = 'pindah';
                            }
                        }
                        $listData['nama_daerah'] = $data;
                        $listData['nama_trayek'] = $trayek;
                        $listData['status'] = $status;
                        
                        $place[] =  $listData;
                    }
                }else{
                    $trayek = isset($process['transit']['angkot'][0]) ? $process['transit']['angkot'][0] : '';
                    $status = '';
                    
                    $listData['nama_daerah'] = $data;
                    $listData['nama_trayek'] = $trayek;
                    $listData['status'] = $status;

                    $place[] =  $listData;
                }
            }
        }

        $countPlace = count($place);

        for ($i = 0; $i <= $countPlace - 1; $i++) {
            $index_tempat = 0;
            $index_angkot = 0;

            $url = "https://maps.googleapis.com/maps/api/directions/json?";
            $origins = "origin=".$place[$i]['pd_latitude'].",".$place[$i]['pd_longitude'];
            $destination = "&destination=".$place[$i+1]['pd_latitude'].",".$place[$i+1]['pd_longitude'];
            $key = "&key=".env("GMAPS_TOKEN");
            $client = new \GuzzleHttp\Client();
            // dd($url.$origins.$destination.$key);
            $res = $client->request('GET', $url.$origins.$destination.$key);
            // dd($res->getStatusCode());
            // dd($res->getHeaderLine('content-type'));
            $res = json_decode($res->getBody(), true);
            
            $group['latitude'] = $place[$i]['pd_latitude'];
            $group['longitude'] = $place[$i]['pd_longitude'];
            
            if ($place[$i]['pd_name'] == $process['transit']['place_name'][$index_tempat]){
                $index_tempat++;
                $index_angkot++;
                if ($index <= count($process['transit']['angkot'])){
                    $group['angkot'] = $process['transit']['angkot'][$index_angkot];
                }
            }else{
                if ($index <= count($process['transit'])){
                    $group['angkot'] = $process['transit']['angkot'][$index_angkot];
                }
            }

            foreach ($res['routes'] as $a){
                $group['polyline'] = str_replace('\\','cHaNgE',$a['overview_polyline']['points']);
                array_push($return_data['maps_detail'],$group);
            }
            
        } 
        // dd($return_data['maps_detail']);
        // dd($place);
        $return_data['maps_detail'] = json_encode($return_data['maps_detail']);
        $return_data['place_detail'] = json_encode($place);
        $return_data['rute'] = json_encode($rute);
        $return_data['lokasi'] = $get_place;

        return view('contentClient.client.ruteTerpendek', $return_data);
    }
}
