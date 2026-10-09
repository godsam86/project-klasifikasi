#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script pelatihan Binary Logistic Regression untuk Sistem Prestasi Siswa (versi web).
Dipanggil oleh PHP (prediksi.php) melalui shell_exec/proc_open.

Input : JSON dikirim lewat STDIN dengan struktur:
        {
          "kkm": 80,
          "mp_keys": ["paidbp", ...],
          "mp_labels": ["PAIDBP", ...],
          "siswa": [
            {"nama": "...", "kehadiran": 95, "kedisiplinan": "Baik",
             "rata_rata": 87.4, "paidbp": 88, ... }, ...
          ]
        }
Output: JSON ke STDOUT berisi log pelatihan, metrik evaluasi, confusion matrix,
        bobot fitur, dan hasil prediksi untuk seluruh siswa.
"""
import sys
import json

if hasattr(sys.stdin, 'reconfigure'):
    sys.stdin.reconfigure(encoding='utf-8')
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')


def keluar_error(pesan):
    print(json.dumps({'sukses': False, 'error': pesan}, ensure_ascii=False))
    sys.exit(0)


def main():
    try:
        import numpy as np
        import pandas as pd
        from sklearn.linear_model import LogisticRegression
        from sklearn.model_selection import train_test_split
        from sklearn.metrics import (accuracy_score, precision_score,
                                      recall_score, f1_score, confusion_matrix)
        from sklearn.preprocessing import MinMaxScaler
    except ImportError as e:
        keluar_error(
            'Modul Python yang dibutuhkan belum terpasang (%s). '
            'Jalankan: pip install -r python/requirements.txt' % str(e)
        )
        return

    try:
        mentah = sys.stdin.read()
        payload = json.loads(mentah)
    except Exception as e:
        keluar_error('Gagal membaca data input: %s' % str(e))
        return

    kkm = payload.get('kkm', 80)
    mp_keys = payload.get('mp_keys', [])
    mp_labels = payload.get('mp_labels', [])
    siswa = payload.get('siswa', [])

    if len(siswa) < 10:
        keluar_error('Data siswa terlalu sedikit untuk melatih model (minimal 10 siswa).')
        return
    if not mp_keys:
        keluar_error('Belum ada kolom mata pelajaran yang terdaftar.')
        return

    df = pd.DataFrame(siswa)
    for k in mp_keys:
        if k not in df.columns:
            df[k] = 0

    def encode_disiplin(series):
        values = []
        for val in series:
            if val is None or (isinstance(val, float) and pd.isna(val)):
                values.append(1.0)
                continue
            if isinstance(val, (int, float, np.number)):
                values.append(float(val))
                continue
            s = str(val).strip()
            if s == '':
                values.append(1.0)
                continue
            lower = s.lower()
            if lower in {'baik', 'sangat baik', 'good', 'excellent'}:
                values.append(2.0)
            elif lower in {'cukup', 'cukup baik', 'sedang', 'average'}:
                values.append(1.0)
            elif lower in {'kurang', 'rendah', 'poor'}:
                values.append(0.0)
            else:
                try:
                    values.append(float(s.replace(',', '.')))
                except ValueError:
                    values.append(1.0)
        return np.asarray(values, dtype=float)

    df['disiplin_enc'] = encode_disiplin(df['kedisiplinan'])

    fitur_display = list(mp_labels) + ['Kehadiran', 'Kedisiplinan']
    log = []
    SEP = '\u2500' * 56
    log += ['=' * 56, '   PROSES PELATIHAN BINARY LOGISTIC REGRESSION', '=' * 56, '']

    X = df[list(mp_keys) + ['kehadiran', 'disiplin_enc']].values.astype(float)
    y = (df['rata_rata'].astype(float) >= kkm).astype(int).values

    bp = int(y.sum())
    bt = len(y) - bp
    log += [
        '  Jumlah Data          : %d siswa' % len(df),
        '  Jumlah Fitur         : %d variabel' % X.shape[1],
        '  Berprestasi (y=1)    : %d siswa' % bp,
        '  Butuh Pembinaan (y=0): %d siswa' % bt, '',
    ]

    if bp < 2 or bt < 2:
        keluar_error(
            'Data tidak seimbang: minimal butuh 2 siswa di tiap kategori '
            '(Berprestasi & Butuh Pembinaan) agar pembagian data latih/uji bisa dilakukan.'
        )
        return

    try:
        X_tr, X_te, y_tr, y_te = train_test_split(
            X, y, test_size=0.2, random_state=42, stratify=y
        )
    except ValueError as e:
        keluar_error('Gagal membagi data latih/uji: %s' % str(e))
        return

    log += [
        '  Pembagian Data (Stratified Random Sampling):',
        '  Data Latih (80%%) : %d siswa' % len(X_tr),
        '  Data Uji  (20%%)  : %d siswa' % len(X_te), '',
    ]
    log.append('  Preprocessing:')
    log.append('  - Label Encoding  : Kedisiplinan -> 0/1/2')
    log.append('  - Normalisasi     : Min-Max Scaler [0,1]')

    scaler = MinMaxScaler()
    X_tr_s = scaler.fit_transform(X_tr)
    X_te_s = scaler.transform(X_te)
    log.append('  - Status          : Selesai')
    log.append('')
    log.append('  Melatih model Binary Logistic Regression...')
    log.append('  Solver: lbfgs | Max Iter: 1000 | Random State: 42')

    clf = LogisticRegression(random_state=42, max_iter=1000, solver='lbfgs')
    clf.fit(X_tr_s, y_tr)
    log.append('  Status: Model berhasil dilatih (konvergen)')
    log.append('')

    y_pred = clf.predict(X_te_s)
    akurasi = float(accuracy_score(y_te, y_pred))
    presisi = float(precision_score(y_te, y_pred, zero_division=0))
    recall = float(recall_score(y_te, y_pred, zero_division=0))
    f1 = float(f1_score(y_te, y_pred, zero_division=0))
    cm = confusion_matrix(y_te, y_pred, labels=[0, 1])

    log += [
        SEP, '  HASIL EVALUASI MODEL:', SEP,
        '  Akurasi  : %.4f (%.2f%%)' % (akurasi, akurasi * 100),
        '  Presisi  : %.4f (%.2f%%)' % (presisi, presisi * 100),
        '  Recall   : %.4f (%.2f%%)' % (recall, recall * 100),
        '  F1-Score : %.4f (%.2f%%)' % (f1, f1 * 100), '',
    ]

    coef_abs = np.abs(clf.coef_[0])
    bobot_pairs = sorted(zip(fitur_display, coef_abs.tolist()), key=lambda x: x[1], reverse=True)
    bobot_fitur = dict(bobot_pairs)

    log.append('  FAKTOR TERKUAT:')
    for i, (f, b) in enumerate(bobot_pairs[:5], 1):
        log.append('  %d. %-22s: %.4f' % (i, f, b))
    log += ['', '=' * 56, '  PELATIHAN SELESAI', '=' * 56]

    # Prediksi untuk seluruh siswa (bukan hanya data uji)
    X_all_s = scaler.transform(X)
    y_pred_all = clf.predict(X_all_s)
    y_prob_all = clf.predict_proba(X_all_s)[:, 1]

    prediksi = []
    for i, row in df.iterrows():
        prediksi.append({
            'nama': row['nama'],
            'rata_rata': float(row['rata_rata']),
            'kategori': row['kategori'],
            'prediksi': 'Berprestasi' if y_pred_all[i] == 1 else 'Butuh Pembinaan',
            'probabilitas': round(float(y_prob_all[i]) * 100, 1),
        })

    hasil = {
        'sukses': True,
        'akurasi': akurasi,
        'presisi': presisi,
        'recall': recall,
        'f1': f1,
        'cm': cm.tolist(),
        'bobot_fitur': bobot_fitur,
        'log': log,
        'prediksi': prediksi,
        'jumlah_data': len(df),
        'jumlah_latih': len(X_tr),
        'jumlah_uji': len(X_te),
    }
    print(json.dumps(hasil, ensure_ascii=False))


if __name__ == '__main__':
    main()
