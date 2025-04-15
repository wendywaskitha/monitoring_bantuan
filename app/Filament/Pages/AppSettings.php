<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Filament\Actions\Action;
use Illuminate\Http\UploadedFile; // Import UploadedFile

class AppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.app-settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Pengaturan Aplikasi';
    protected static ?string $navigationLabel = 'Tampilan & Branding';
    protected static ?string $title = 'Pengaturan Tampilan Aplikasi';
    protected static ?int $navigationSort = 1;

    // Properti publik untuk menampung data form
    public ?array $data = [];

    public function mount(): void
    {
        // Ambil semua setting dari DB
        $settings = Setting::pluck('value', 'key')->all();
        // Isi form dengan data dari DB (akan mengisi properti $data)
        // Untuk FileUpload, kita perlu path relatif dari disk public
        // Jika value adalah path file, biarkan path tersebut
        $this->form->fill($settings);
        Log::info('AppSettings form filled with initial data:', $settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Nama Aplikasi')
                            ->helperText('Ditampilkan di judul tab, navbar, dll.')
                            ->required(),
                        TextInput::make('app_subtitle')
                            ->label('Subtitle/Slogan Aplikasi')
                            ->nullable()
                            ->helperText('Tampil di bawah nama aplikasi (opsional).'),
                    ]),
                Section::make('Branding Visual')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo Aplikasi (Light Mode / Default)')
                            ->disk('public')
                            ->directory('branding') // Folder penyimpanan di storage/app/public/branding
                            ->image()
                            ->maxSize(1024) // Max 1MB
                            ->helperText('Kosongkan jika tidak ingin mengubah logo. Unggah file baru untuk mengganti.')
                            ->placeholder('Upload logo baru')
                            // ->imagePreviewHeight('60') // Atur tinggi preview jika perlu
                            // ->loadingIndicatorPosition('left')
                            // ->panelAspectRatio('2:1')
                            // ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left'),
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->disk('public')
                            ->directory('branding')
                            ->acceptedFileTypes(['image/vnd.microsoft.icon', 'image/x-icon', 'image/png', 'image/svg+xml'])
                            ->maxSize(512) // Max 512KB
                            ->helperText('Format .ico, .png, atau .svg. Kosongkan jika tidak ingin mengubah.')
                            ->placeholder('Upload favicon baru'),
                    ]),
            ])
            ->statePath('data'); // Hubungkan state form ke properti $data
    }

    // Method untuk tombol simpan
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Perubahan')
                ->submit('save'),
        ];
    }

    /**
     * Method save untuk menyimpan data dari form.
     */
     public function save(): void
     {
        Log::info('--- AppSettings Save Method Started ---');
        try {
            $formData = $this->form->getState();
            Log::info('Form data state retrieved for saving:', $formData);

            $appNameChanged = false;

            foreach ($formData as $key => $value) {
                if (!in_array($key, ['app_name', 'app_subtitle', 'logo_path', 'favicon_path'])) {
                    Log::warning("Skipping unknown key: {$key}");
                    continue;
                }
                Log::debug("Processing key: {$key}");

                $setting = Setting::firstOrNew(['key' => $key]);
                $currentValue = $setting->value;
                $newValue = $value; // Default: nilai baru = nilai dari form
                $valueChanged = false;

                // Handle File Uploads
                if ($this->isFileUploadKey($key)) {
                    Log::debug("Processing file key: {$key}", ['value_type' => gettype($value), 'current_db_value' => $currentValue]);

                    // Kasus 1: File BARU diupload
                    if ($value instanceof UploadedFile) { // Lebih eksplisit cek instance
                        Log::debug("New file uploaded instance detected for {$key}.");
                        // Hapus file lama jika ada
                        if ($currentValue && Storage::disk('public')->exists($currentValue)) {
                            if (Storage::disk('public')->delete($currentValue)) { Log::info("Deleted old file for {$key}: {$currentValue}"); }
                            else { Log::error("Failed to delete old file for {$key}: {$currentValue}"); }
                        }
                        // Simpan file baru
                        $path = $value->store('branding', 'public');
                        if ($path) {
                            $newValue = $path; // Set nilai baru dengan path
                            $valueChanged = true; // Tandai ada perubahan
                            Log::info("Stored new file for {$key}: {$path}. New value set to: {$newValue}");
                        } else {
                            Log::error("Failed to store new file for {$key}");
                            Notification::make()->title("Gagal menyimpan file untuk {$key}")->danger()->send();
                            continue; // Jangan proses save DB jika store gagal
                        }
                    }
                    // Kasus 2: File DIHAPUS di form (value adalah array kosong)
                    elseif (is_array($value) && empty($value)) {
                        Log::debug("File input cleared for {$key}. Old path: {$currentValue}");
                        if ($currentValue && Storage::disk('public')->exists($currentValue)) {
                            if (Storage::disk('public')->delete($currentValue)) { Log::info("Successfully deleted old file for {$key} because input was cleared."); }
                            else { Log::error("Failed to delete old file for {$key} when clearing input."); }
                        }
                        if ($currentValue !== null) {
                            $newValue = null; // Set nilai baru jadi null
                            $valueChanged = true; // Tandai ada perubahan
                        } else {
                            $newValue = null; // Tetap null jika sebelumnya sudah null
                            $valueChanged = false; // Tidak ada perubahan dari null ke null
                        }
                        Log::info("File cleared for {$key}. New value set to: null");
                    }
                    // Kasus 3: File TIDAK DIUBAH (value adalah path string lama atau null)
                    else {
                        // Nilai dari form ($value) akan berisi path lama atau null
                        // Kita tidak perlu melakukan apa-apa, biarkan $newValue = $value
                        // dan $valueChanged akan dicek di bawah.
                        Log::debug("File not changed for key: {$key}. Value from form: " . json_encode($value));
                        $newValue = $value; // Pastikan newValue sesuai state form
                        if ($setting->value !== $newValue) { // Cek perubahan eksplisit
                            $valueChanged = true;
                        }
                    }
                }
                // Handle Nilai Biasa (Non-File)
                else {
                    if ($setting->value !== $newValue) {
                        $valueChanged = true;
                        Log::debug("Value changed for key: {$key} from '{$setting->value}' to '{$newValue}'");
                    } else {
                         Log::debug("Value not changed for key: {$key}");
                    }
                }

                // Simpan ke DB HANYA jika ada perubahan atau record baru
                if ($valueChanged || !$setting->exists) {
                    $setting->value = $newValue; // Set nilai baru ke model
                    Log::info("Attempting to save setting '{$key}' with value: " . var_export($setting->value, true));
                    if ($setting->save()) {
                        Log::info("Setting '{$key}' SAVED successfully.");
                        if ($key === 'app_name') { $appNameChanged = true; }
                        if (function_exists('clear_setting_cache')) { clear_setting_cache($key); }
                        else { Cache::forget('settings.' . $key); }
                    } else {
                         Log::error("Failed to save setting '{$key}' to database.");
                         // Mungkin kirim notifikasi gagal save DB?
                         Notification::make()->title("Gagal menyimpan setting {$key} ke database.")->danger()->send();
                    }
                } else {
                    Log::info("Setting '{$key}' skipped saving (no changes).");
                }

            } // Akhir foreach

             // Hapus cache config Laravel HANYA jika nama aplikasi benar-benar berubah
             if ($appNameChanged) {
                 Artisan::call('config:clear');
                 Artisan::call('config:cache');
                 Log::info("Config cache cleared and recached due to app_name change.");
             }

             // Kirim Notifikasi Sukses (setelah loop selesai)
             Notification::make()->title('Pengaturan berhasil disimpan.')->success()->send();
             Log::info('--- AppSettings Save Method Finished Successfully ---');

             // Refresh state form dengan data terbaru dari DB setelah save
             // Ini penting agar preview FileUpload terupdate
             $this->form->fill(Setting::pluck('value', 'key')->all());

             // Opsional: refresh halaman penuh jika perlu (misal update favicon browser)
             // $this->dispatch('refresh-page');

         } catch (\Illuminate\Validation\ValidationException $e) {
             Notification::make()->title('Data tidak valid.')->danger()->send();
             Log::error("Validation error saving app settings: ", $e->errors());
         } catch (\Exception $e) {
             Notification::make()->title('Gagal menyimpan pengaturan.')->body($e->getMessage())->danger()->send();
             Log::error("Error saving app settings: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
             Log::info('--- AppSettings Save Method Failed ---');
         }
     }

     // Helper untuk cek apakah key adalah field file upload
     private function isFileUploadKey(string $key): bool
     {
         return in_array($key, ['logo_path', 'favicon_path']);
     }

     /**
      * Otorisasi akses ke halaman ini.
      */
     public static function canAccess(): bool
     {
         // Ganti dengan permission check yang benar
         return auth()->user()?->can('manage_app_settings') ?? (auth()->user()?->hasRole('Admin') ?? false);
     }
}
