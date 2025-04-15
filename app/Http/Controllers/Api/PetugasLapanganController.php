<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelompok;
use App\Models\DistribusiBantuan;
use App\Models\MonitoringPemanfaatan;
use App\Models\PelaporanHasil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PetugasLapanganController extends Controller
{
    /**
     * Get all groups assigned to the authenticated Petugas Lapangan
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function kelompok(Request $request)
    {
        $user = $request->user();
        $kelompoks = $user->kelompokYangDitangani()
            ->with(['kecamatan', 'desa'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'kelompoks' => $kelompoks
            ]
        ]);
    }

    /**
     * Get details of a specific group
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function kelompokDetail(Request $request, $id)
    {
        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()
            ->with(['kecamatan', 'desa', 'anggotas'])
            ->find($id);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'kelompok' => $kelompok
            ]
        ]);
    }

    /**
     * Get all bantuan distributions for a specific group
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $kelompokId
     * @return \Illuminate\Http\JsonResponse
     */
    public function distribusiBantuan(Request $request, $kelompokId)
    {
        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()->find($kelompokId);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $distribusis = DistribusiBantuan::where('kelompok_id', $kelompokId)
            ->with(['bantuan'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'distribusis' => $distribusis
            ]
        ]);
    }

    /**
     * Create a new bantuan distribution record
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $kelompokId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeDistribusiBantuan(Request $request, $kelompokId)
    {
        $validator = Validator::make($request->all(), [
            'bantuan_id' => 'required|exists:bantuans,id',
            'tanggal_distribusi' => 'required|date',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()->find($kelompokId);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $distribusi = new DistribusiBantuan();
        $distribusi->kelompok_id = $kelompokId;
        $distribusi->bantuan_id = $request->bantuan_id;
        $distribusi->tanggal_distribusi = $request->tanggal_distribusi;
        $distribusi->jumlah = $request->jumlah;
        $distribusi->keterangan = $request->keterangan;
        $distribusi->user_id = $user->id;
        $distribusi->save();

        return response()->json([
            'status' => true,
            'message' => 'Data distribusi bantuan berhasil disimpan',
            'data' => [
                'distribusi' => $distribusi
            ]
        ], 201);
    }

    /**
     * Get all monitoring records for a specific group
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $kelompokId
     * @return \Illuminate\Http\JsonResponse
     */
    public function monitoringPemanfaatan(Request $request, $kelompokId)
    {
        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()->find($kelompokId);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $monitorings = MonitoringPemanfaatan::where('kelompok_id', $kelompokId)
            ->with(['bantuan'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'monitorings' => $monitorings
            ]
        ]);
    }

    /**
     * Create a new monitoring record
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $kelompokId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeMonitoringPemanfaatan(Request $request, $kelompokId)
    {
        $validator = Validator::make($request->all(), [
            'bantuan_id' => 'required|exists:bantuans,id',
            'tanggal_monitoring' => 'required|date',
            'status_pemanfaatan' => 'required|in:Baik,Cukup,Kurang',
            'keterangan' => 'nullable|string|max:1000',
            'foto' => 'nullable|string', // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()->find($kelompokId);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $monitoring = new MonitoringPemanfaatan();
        $monitoring->kelompok_id = $kelompokId;
        $monitoring->bantuan_id = $request->bantuan_id;
        $monitoring->tanggal_monitoring = $request->tanggal_monitoring;
        $monitoring->status_pemanfaatan = $request->status_pemanfaatan;
        $monitoring->keterangan = $request->keterangan;
        $monitoring->user_id = $user->id;
        
        // Handle photo upload if provided
        if ($request->has('foto') && !empty($request->foto)) {
            // Decode base64 image and save it
            $image_parts = explode(";base64,", $request->foto);
            $image_base64 = base64_decode($image_parts[1]);
            $filename = 'monitoring_' . time() . '.jpg';
            $path = 'monitoring/' . $filename;
            
            // Save the image to storage
            \Storage::disk('public')->put($path, $image_base64);
            
            $monitoring->foto_path = $path;
        }
        
        $monitoring->save();

        return response()->json([
            'status' => true,
            'message' => 'Data monitoring pemanfaatan berhasil disimpan',
            'data' => [
                'monitoring' => $monitoring
            ]
        ], 201);
    }

    /**
     * Get all reports for a specific group
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $kelompokId
     * @return \Illuminate\Http\JsonResponse
     */
    public function pelaporanHasil(Request $request, $kelompokId)
    {
        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()->find($kelompokId);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $pelaporans = PelaporanHasil::where('kelompok_id', $kelompokId)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'pelaporans' => $pelaporans
            ]
        ]);
    }

    /**
     * Create a new report
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $kelompokId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePelaporanHasil(Request $request, $kelompokId)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_pelaporan' => 'required|date',
            'judul_pelaporan' => 'required|string|max:255',
            'isi_pelaporan' => 'required|string',
            'foto' => 'nullable|string', // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $kelompok = $user->kelompokYangDitangani()->find($kelompokId);

        if (!$kelompok) {
            return response()->json([
                'status' => false,
                'message' => 'Kelompok tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $pelaporan = new PelaporanHasil();
        $pelaporan->kelompok_id = $kelompokId;
        $pelaporan->tanggal_pelaporan = $request->tanggal_pelaporan;
        $pelaporan->judul_pelaporan = $request->judul_pelaporan;
        $pelaporan->isi_pelaporan = $request->isi_pelaporan;
        $pelaporan->user_id = $user->id;
        
        // Handle photo upload if provided
        if ($request->has('foto') && !empty($request->foto)) {
            // Decode base64 image and save it
            $image_parts = explode(";base64,", $request->foto);
            $image_base64 = base64_decode($image_parts[1]);
            $filename = 'pelaporan_' . time() . '.jpg';
            $path = 'pelaporan/' . $filename;
            
            // Save the image to storage
            \Storage::disk('public')->put($path, $image_base64);
            
            $pelaporan->foto_path = $path;
        }
        
        $pelaporan->save();

        return response()->json([
            'status' => true,
            'message' => 'Data pelaporan hasil berhasil disimpan',
            'data' => [
                'pelaporan' => $pelaporan
            ]
        ], 201);
    }
} 