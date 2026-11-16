<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Turkish strings for local_wamakademi.
 *
 * @package    local_wamakademi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'WAM Akademi';
$string['adminnav'] = 'Site yönetimi kategorileri';
$string['adminnavtitle'] = 'Site yönetimi';
$string['adminnavopen'] = 'Yönetim menüsü';
$string['adminnavclose'] = 'Yönetim menüsünü kapat';
$string['adminsearchlabel'] = 'Yönetim ayarlarında ara';
$string['adminsearchplaceholder'] = 'Yönetim ayarlarında ara…';
$string['adminsearchhint'] = 'Yalnızca bu sayfadaki bağlantıları değil, sitedeki tüm ayarları arar.';
$string['adminsearchsubmit'] = 'Ayarlarda ara';
$string['adminsearchresults'] = 'Arama sonuçları';
$string['adminsearchnoresults'] = 'Aramanızla eşleşen ayar bulunamadı';
$string['adminsearchnoresultshelp'] = 'Yazımı kontrol edin, daha kısa bir kelime deneyin veya yönetim menüsündeki kategorilere göz atın.';
$string['adminsearchclear'] = 'Aramayı temizle';
$string['adminhomedesc'] = 'Site genelindeki ayarları, kullanıcıları, kursları, eklentileri ve raporları yönetin. Soldan bir kategori seçin ya da ayar arayın.';
$string['adminbreadcrumb'] = 'Yönetim';
$string['adminsearchcount'] = '{$a} eşleşen sayfa';
$string['adminsearchtooshort'] = 'Ayarlarda aramak için en az iki karakter yazın';
$string['courseaddparticipants'] = 'Katılımcı ekle';
$string['coursesearchlabel'] = 'Ad veya e-posta ile ara';
$string['coursesearchplaceholder'] = 'Ad, soyad veya e-posta…';
$string['coursesearchsubmit'] = 'Ara';
$string['coursesearchclear'] = 'Aramayı temizle';
$string['courseadvancedfilters'] = 'Gelişmiş filtreler';
$string['courseinitials'] = 'Baş harfe göre';
$string['courseindexshort'] = 'Kurs dizini';
$string['participantsfirstname'] = 'Ad';
$string['participantslastname'] = 'Soyad';
$string['participantsemail'] = 'E-posta';
$string['participantsrole'] = 'Rol';
$string['participantsgroup'] = 'Grup';
$string['participantslastaccess'] = 'Son erişim';
$string['participantsstatus'] = 'Durum';
$string['participantsactions'] = 'İşlemler';
$string['participantsactionsfor'] = 'İşlemler: {$a}';
$string['participantseditroles'] = 'Rolü düzenle';
$string['participantseditgroups'] = 'Grubu düzenle';
$string['participantsenroldetails'] = 'Kayıt bilgileri';
$string['participantseditenrol'] = 'Kaydı düzenle';
$string['participantsunenrol'] = 'Eğitim kaydını kaldır';
$string['participantstablelabel'] = 'Katılımcılar tablosu';
$string['formrequired'] = 'Zorunlu';

// Kullanıcı ve katılımcı ekranları (User R1): bağlam adlandırma ve kapsam açıklamaları.
$string['participantsrolecourse'] = 'Eğitim rolü';
$string['participantsrolesite'] = 'Ana sayfa rolü';
$string['participantslastaccesscourse'] = 'Eğitime son erişim';
$string['participantslastaccesssite'] = 'Siteye son erişim';
$string['participantstitlecourse'] = '{$a} — Katılımcılar';
$string['participantstitlesite'] = 'Site ana sayfası — Katılımcılar';
$string['participantsscopecourse'] = 'Bu eğitime kayıtlı kullanıcılar; rol, grup, durum ve son erişim yalnızca bu eğitim için geçerlidir. “Katılımcı ekle” var olan bir hesabı eğitime kaydeder, yeni hesap oluşturmaz.';
$string['participantsscopesite'] = 'Sitedeki tüm kullanıcı hesapları; rol, grup ve son erişim site ana sayfası bağlamına aittir, eğitim kayıtlarını göstermez.';
$string['participantsscopesitenew'] = 'Yeni hesap oluşturmak için {$a} kullanılır.';
$string['userslisttitle'] = 'Sistem kullanıcıları';
$string['userslistscope'] = 'Sitedeki tüm kullanıcı hesapları. Eğitim kayıtları, eğitim rolleri ve gruplar her eğitimin Katılımcılar ekranından yönetilir.';
$string['usersdeleteaccount'] = 'Hesabı sil';
$string['usersnewscope'] = 'Yeni bir sistem hesabı oluşturur; hesabı bir eğitime kaydetmez. Eğitime kayıt için eğitimin Katılımcılar ekranındaki “Katılımcı ekle” kullanılır.';
$string['userseditscope'] = 'Sistem hesabının bilgilerini düzenler. Eğitim rolleri ve kayıtlar bu ekranda değişmez; eğitimin Katılımcılar ekranından yönetilir.';
$string['userseditheading'] = 'Kullanıcıyı düzenle — {$a}';

// İçerik iş akışı (Content Workflow R1).
$string['wamakademi:managevideos'] = 'Eğitim videolarını yükle, önizle ve yayımla';
$string['menucreatecourse'] = 'Kurs oluştur';
$string['menuuploadvideo'] = 'Video yükle';
$string['uploadtitle'] = 'Video yükle';
$string['uploadheading'] = '{$a} — Video yükle';
$string['uploadscope'] = 'MP4 dosyasını gizli taslak olarak kaydeder; öğrenci görmez. Önizledikten sonra yayımlayabilirsiniz.';
$string['uploadcourse'] = 'Eğitim';
$string['uploadsection'] = 'Bölüm';
$string['uploadnewsection'] = 'Yeni bölüm adı (isteğe bağlı)';
$string['uploadlessonname'] = 'Ders adı';
$string['uploadintro'] = 'Açıklama (isteğe bağlı)';
$string['uploadfile'] = 'MP4 dosyası';
$string['uploaddraftsave'] = 'Taslak kaydet';
$string['uploadcancel'] = 'İptal';
$string['uploadsubmitting'] = 'Yükleniyor…';
$string['uploaddraftsaved'] = 'Video gizli taslak olarak kaydedildi.';
$string['uploadduplicate'] = 'Bu gönderim zaten işlendi; mevcut taslağa yönlendirildiniz.';
$string['uploadnoperm'] = 'Bu eğitimde video yönetme yetkiniz yok.';
$string['uploadnocourses'] = 'Video yükleyebileceğiniz bir eğitim bulunamadı.';
$string['previewstatusdraft'] = 'Taslak';
$string['previewstatuspublished'] = 'Yayında';
$string['previewstatusrestricted'] = 'Yayında ama öğrenciye kapalı';
$string['previewedit'] = 'Düzenle';
$string['previewpublish'] = 'Yayımla';
$string['previewunpublish'] = 'Yayından kaldır';
$string['previewback'] = 'Eğitime dön';
$string['previewopenplayer'] = 'WAM oynatıcıda aç';
$string['publishsuccess'] = 'İçerik yayımlandı; yetkili öğrenciler görebilir.';
$string['unpublishsuccess'] = 'İçerik yayından kaldırıldı; öğrenciler göremez.';
$string['publishforbidden'] = 'Yayımlama yetkiniz yok.';
$string['publishinvalid'] = 'Geçersiz istek.';
$string['hiddendrafts'] = 'Gizli taslaklar';
$string['norestriction'] = '';
$string['restrictioncourse'] = 'Eğitim gizli';
$string['restrictionsection'] = 'Bölüm gizli';
$string['restrictionavailability'] = 'Erişim kısıtı: {$a}';
