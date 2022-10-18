<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;

class BeritaController extends Controller
{
    public function index()
    {
        $BeritaKategori = DB::table('kategori_berita')->get();

        $data = [
            'BeritaKategori' => $BeritaKategori,
            'script'            => 'components.scripts.berita'
        ];

        return view('pages.berita', $data);
    }
    private $token;

    public function guzzle()
    {


        $client = new Client();
        $url = 'https://survey.stb.web.id/api/progress/questionnaire';
        $json = [
            'username' => 'relawan',
            'password' => '12345678'

        ];

        $headers = [
            'Accept' => 'application/json',
            'Authorization'=> 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMGNmMTM0NjlmY2ZjNzljYmNkYTFlNGI3ZDEyOTE0MGE5NjMxMDNmZWNmY2JmZGVkMjMzZjM4ZGUwM2U5YWEyOGY1ODI5YWMyZTdiNTk4ZTciLCJpYXQiOjE2NjYwNzk2MTMuNjQ5MzAyOTU5NDQyMTM4NjcxODc1LCJuYmYiOjE2NjYwNzk2MTMuNjQ5MzA2MDU4ODgzNjY2OTkyMTg3NSwiZXhwIjoxNjk3NjE1NjEzLjY0MTgwODAzMjk4OTUwMTk1MzEyNSwic3ViIjoiMiIsInNjb3BlcyI6W119.uTLVc2e9WwDHUO8xJVg_mKqc-sAxlPGbI8Ucv3F0XwUcftsSfgCrwM8ntPEsFGykHznAaddLuamhOl-2mz-Whiy2oGWbC4Brh7BRk08D9cC6fPrJSl5Sl3Lz5QUdwMVXO_bzYgx_GUPIjstm-3IGIXyQ5nxaYtudraOjNfoO1JMgMlJmsNg2rm6uO3i8nxxipu5cK-picLLAqowkDkjwUusg1Uy1U7DdUooW99TMqzRCr5oMwqSHaIzTQM6rjkzQ1s3HD8bBNwrpIuXMoKvh-s1XMxgU4opj429dQaSlHHvklwgQqLcxj5pZZyVPm0zB_WDnji6_Wq7vMFUpn7mFf1omf-GJCRY8MkjeDNLneWEXomDv8WHgonsaKI_wOPgRmIypbpzeZoaqYoDhsm7-aVdBAEk7TwR2szffkFKkkBqkDgc-zb37_7ainHBQW0Zxz9swpnY4VpTu_vKfW7roSTKs1HXLvet3SuzJ56PQIcXVJn4IXnGsB7kE2QoT-Hq12zJ6xwLmeITKe1PYKgV_mNAueS8pEBcDE_Z4ViBRaH1uWpCWFmSKGPLuGWGzFimlJr-zQ8L-rJHuHxxhTfp6T8hN6dU6c33ol6IBhs1Fht4i1NSXKi4FerjyBp6FeIkMS7zRpX3fH_pX9KcwZsM_NdhOeDO2Xbo9Ttf87OFKdX0'
        ];

        $response = $client->request('GET',$url,[
            'headers' => $headers,
            'json' => $json,
        ]
        );

        $data = json_decode($response->getBody(), true);
        //$data['data']['token'];
        dd($data);



    }

    public function show($id) {
        if(is_numeric($id)) {
            $data = DB::table('berita')->where('id', $id)->first();



            return Response::json($data);
        }

        $data = DB::table('berita')
            ->join('kategori_berita', 'berita.post_cat_id', '=', 'kategori_berita.id')
            ->select([
                'berita.*', 'kategori_berita.title as kategori'
            ])
            ->orderBy('berita.id', 'desc');

        return DataTables::of($data)
            ->editColumn(
                'status',
                function($row) {
                    return $row->status;
                }
            )
            ->editColumn(
                'photo',
                function($row) {
                    $data = [
                        'photo' => $row->photo
                    ];

                    return view('components.img.berita', $data);
                }
            )
            ->addColumn(
                'action',
                function($row) {
                    $data = [
                        'id' => $row->id
                    ];

                    return view('components.buttons.berita', $data);
                }
            )
            ->addIndexColumn()
            ->make(true);
    }

    public function store(Request $request)
    {
      $extension = $request->file('cover')->getClientOriginalExtension();

      $cover = date('YmdHis').'.'.$extension;

      $path = base_path('public/photo_berita');

      $request->file('cover')->move($path, $cover);
      //dd($extension);
        if($request->title == NULL) {
            $json = [
                'msg'       => 'Mohon masukan judul berita',
                'status'    => false
            ];
        } elseif(!$request->has('berita_kategori_id')) {
            $json = [
                'msg'       => 'Mohon pilih kategori berita',
                'status'    => false
            ];
        } elseif($request->quote == NULL) {
            $json = [
                'msg'       => 'Mohon masukan ringkasan berita',
                'status'    => false
            ];
        } elseif($request->summary == NULL) {
            $json = [
                'msg'       => 'Mohon masukan ringkasan berita',
                'status'    => false
            ];
        } elseif($request->description == NULL) {
            $json = [
                'msg'       => 'Mohon masukan deskripsi berita',
                'status'    => false
            ];
        }else {
            try{
                DB::transaction(function() use($request,$cover) {
                    DB::table('berita')->insert([
                        'created_at' => date('Y-m-d H:i:s'),
                        'title' => $request->title,
                        'slug' => Str::slug($request->title),
                        'post_cat_id' => $request->berita_kategori_id,
                        'summary' => $request->summary,
                        'quote' => $request->quote,
                        'description' => $request->description,
                        'added_by' =>  Auth::user()->id ,
                        'photo' => $cover,
                        'status' => 'active',
                    ]);
                });

                $json = [
                    'msg' => 'Produk berhasil ditambahkan',
                    'status' => true
                ];
            } catch(Exception $e) {
                $json = [
                    'msg'       => 'error',
                    'status'    => false,
                    'e'         => $e
                ];
            }
        }

        return Response::json($json);
    }

    public function edit(Request $request, $id)
    {
      $extension = $request->file('coverEdit')->getClientOriginalExtension();

      $coverEdit = date('YmdHis').'.'.$extension;

      $path = base_path('public/photo_berita');

      $request->file('coverEdit')->move($path, $coverEdit );

        if($request->title == NULL) {
            $json = [
                'msg'       => 'Mohon masukan judul berita',
                'status'    => false
            ];
        } elseif(!$request->has('berita_kategori_id')) {
            $json = [
                'msg'       => 'Mohon pilih kategori berita',
                'status'    => false
            ];
        } elseif($request->quoteEdit == NULL) {
            $json = [
                'msg'       => 'Mohon masukan kutipan',
                'status'    => false
            ];
        }elseif($request->summaryEdit == NULL) {
            $json = [
                'msg'       => 'Mohon masukan ringkasan',
                'status'    => false
            ];
        }elseif($request->descriptionEdit == NULL) {
            $json = [
                'msg'       => 'Mohon masukan Deskripsi',
                'status'    => false
            ];
        } else {
            try{
              DB::transaction(function() use($request, $id, $coverEdit) {
                  DB::table('berita')->where('id', $id)->update([
                      'updated_at' => date('Y-m-d H:i:s'),
                      'title' => $request->title,
                      'slug' => Str::slug($request->title),
                      'post_cat_id' => $request->berita_kategori_id,
                      'summary' => $request->summaryEdit,
                      'quote' => $request->quoteEdit,
                      'description' => $request->descriptionEdit,
                      'added_by' =>  Auth::user()->id ,
                      'photo' => $coverEdit,
                      'status' => 'active',

                  ]);
              });

                $json = [
                    'msg' => 'Berita berhasil dirubah',
                    'status' => true
                ];
            } catch(Exception $e) {
                $json = [
                    'msg'       => 'error',
                    'status'    => false,
                    'e'         => $e
                ];
            }
        }

        return Response::json($json);
    }

    public function destroy($id)
    {

            try{

              DB::transaction(function() use($id) {
                  DB::table('berita')->where('id', $id)->delete();
              });

                $json = [
                    'msg' => 'Berita berhasil dihapus',
                    'status' => true
                ];
            } catch(Exception $e) {
                $json = [
                    'msg'       => 'error',
                    'status'    => false,
                    'e'         => $e
                ];
            }


        return Response::json($json);
    }


}
