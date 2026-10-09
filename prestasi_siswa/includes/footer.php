    </div>
    <footer class="appfoot">© 2025 - Sistem Prestasi Siswa | SD Negeri 060921 Medan</footer>
  </div>
</div>
<script>
function perbaruiJam(){
  var d = new Date();
  var hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][d.getDay()];
  var pad = n => String(n).padStart(2,'0');
  document.getElementById('jamSekarang').textContent =
    hari + ', ' + pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear() +
    ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
}
perbaruiJam(); setInterval(perbaruiJam, 1000);
</script>
</body>
</html>
