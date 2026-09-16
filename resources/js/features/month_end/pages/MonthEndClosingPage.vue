<template>
  <div class="space-y-3">
    <!-- 1. Header Toolbar -->
    <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-2">
          <svg
            class="w-5 h-5 text-rose-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          Tutup Buku Bulanan (Month-End Closing)
        </h1>
        <p class="text-xs text-gray-500 mt-0.5">
          Kunci periode transaksi persediaan dan bekukan saldo akhir bulanan (<span class="italic">snapshot</span>) untuk audit serta laporan keuangan FAD/BIC.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
          :disabled="loading"
          @click="fetchPeriods"
        >
          <svg
            class="w-3.5 h-3.5 text-gray-500"
            :class="{ 'animate-spin': loading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>Refresh</span>
        </button>

        <button
          v-if="canClosePeriod"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 transition-colors cursor-pointer whitespace-nowrap"
          @click="openCloseModal()"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
          <span>Tutup Buku Periode</span>
        </button>
      </div>
    </div>

    <!-- Alert / Toast Messages -->
    <div
      v-if="feedbackMessage"
      class="rounded-xl border p-3 flex items-start justify-between gap-3 text-xs"
      :class="feedbackType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'"
    >
      <div class="flex items-center gap-2">
        <svg
          v-if="feedbackType === 'success'"
          class="w-4 h-4 text-emerald-600 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
        <svg
          v-else
          class="w-4 h-4 text-rose-600 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ feedbackMessage }}</span>
      </div>
      <button
        type="button"
        class="text-gray-400 hover:text-gray-600 cursor-pointer"
        @click="feedbackMessage = ''"
      >
        &times;
      </button>
    </div>

    <!-- 2. Status & Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Card 1: Periode Berjalan -->
      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs flex flex-col justify-between">
        <div class="flex items-center justify-between text-xs text-gray-500">
          <span>Periode Bulan Ini</span>
          <span
            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
            :class="currentMonthPeriod?.status === 'CLOSED' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
          >
            {{ currentMonthPeriod?.status === 'CLOSED' ? 'DITUTUP' : 'TERBUKA (OPEN)' }}
          </span>
        </div>
        <div class="mt-2">
          <div class="text-sm font-bold text-gray-900">
            {{ currentMonthLabel }}
          </div>
          <div class="text-[11px] text-gray-500 mt-0.5">
            Transaksi aktif diizinkan jika OPEN
          </div>
        </div>
      </div>

      <!-- Card 2: Tutup Buku Terakhir -->
      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs flex flex-col justify-between">
        <div class="text-xs text-gray-500">
          Tutup Buku Terakhir
        </div>
        <div class="mt-2">
          <div class="text-sm font-bold text-gray-900">
            {{ lastClosedPeriod ? lastClosedPeriod.month_name : 'Belum pernah' }}
          </div>
          <div class="text-[11px] text-gray-500 mt-0.5">
            <template v-if="lastClosedPeriod">
              {{ formatTimestamp(lastClosedPeriod.closed_at) }} &bull; {{ lastClosedPeriod.closed_by?.name || 'Sistem' }}
            </template>
            <template v-else>
              Belum ada data penutupan buku
            </template>
          </div>
        </div>
      </div>

      <!-- Card 3: Valuasi Persediaan Terakhir -->
      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs flex flex-col justify-between">
        <div class="text-xs text-gray-500">
          Total Valuasi Terakhir Dibekukan
        </div>
        <div class="mt-2">
          <div class="text-base font-extrabold text-emerald-600">
            {{ formatRupiah(lastClosedPeriod?.total_valuation || 0) }}
          </div>
          <div class="text-[11px] text-gray-500 mt-0.5">
            {{ formatQuantity(lastClosedPeriod?.total_closing_qty || 0) }} unit barang tersimpan
          </div>
        </div>
      </div>

      <!-- Card 4: Deteksi Transaksi Draft -->
      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs flex flex-col justify-between">
        <div class="flex items-center justify-between text-xs text-gray-500">
          <span>Integritas Dokumen</span>
          <span
            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
            :class="pendingDraftsCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
          >
            {{ pendingDraftsCount > 0 ? `${pendingDraftsCount} Draft Pending` : 'Siap Ditutup' }}
          </span>
        </div>
        <div class="mt-2">
          <div class="text-xs font-semibold text-gray-800">
            {{ pendingDraftsCount > 0 ? 'Ada transaksi belum diposting' : 'Semua transaksi terposting rapi' }}
          </div>
          <div
            class="text-[11px] text-gray-500 mt-0.5 truncate"
            :title="pendingDraftsSummary"
          >
            {{ pendingDraftsSummary || 'Tidak ada draft tertinggal' }}
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Tabel Riwayat Periode -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h2 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
            Riwayat Periode Pembukuan Bulanan
          </h2>
          <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-[10px] font-bold">
            {{ periods.length }} Periode
          </span>
        </div>

        <!-- Filter Search -->
        <div class="w-full sm:w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari bulan / tahun..."
            class="w-full text-xs rounded-lg border border-gray-200 px-3 py-1.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 focus:outline-hidden"
          >
        </div>
      </div>

      <!-- Table Body -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
          <thead class="bg-gray-50 text-gray-600 font-semibold">
            <tr>
              <th
                scope="col"
                class="py-2.5 px-3 text-left"
              >
                Periode
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-left"
              >
                Rentang Tanggal
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-center"
              >
                Status
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-left"
              >
                Ditutup Pada & Oleh
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-left"
              >
                Audit Buka Kembali
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-right"
              >
                Saldo Fisik
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-right"
              >
                Valuasi Persediaan
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-center"
              >
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-if="loading"
              class="text-center"
            >
              <td
                colspan="8"
                class="py-8 text-gray-400"
              >
                <div class="flex items-center justify-center gap-2">
                  <svg
                    class="w-4 h-4 animate-spin text-rose-600"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8v8H4z"
                    />
                  </svg>
                  <span>Memuat data periode...</span>
                </div>
              </td>
            </tr>

            <tr
              v-else-if="filteredPeriods.length === 0"
              class="text-center"
            >
              <td
                colspan="8"
                class="py-8 text-gray-400"
              >
                Belum ada data periode yang cocok. Silakan lakukan tutup buku pertama.
              </td>
            </tr>

            <tr
              v-for="item in filteredPeriods"
              :key="item.id"
              class="hover:bg-gray-50/80 transition-colors"
            >
              <!-- Periode -->
              <td class="py-2.5 px-3 whitespace-nowrap font-bold text-gray-900">
                <div class="flex items-center gap-1.5">
                  <span class="text-gray-900">{{ item.month_name }}</span>
                  <span class="text-[10px] text-gray-400 font-mono">({{ item.period_key }})</span>
                </div>
              </td>

              <!-- Rentang Tanggal -->
              <td class="py-2.5 px-3 whitespace-nowrap text-gray-500 font-mono text-[11px]">
                {{ formatDateIndo(item.start_date) }} - {{ formatDateIndo(item.end_date) }}
              </td>

              <!-- Status Badge -->
              <td class="py-2.5 px-3 whitespace-nowrap text-center">
                <span
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                  :class="item.status === 'CLOSED' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                >
                  <svg
                    v-if="item.status === 'CLOSED'"
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                  </svg>
                  <svg
                    v-else
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"
                    />
                  </svg>
                  <span>{{ item.status === 'CLOSED' ? 'Ditutup (Closed)' : 'Terbuka (Open)' }}</span>
                </span>
              </td>

              <!-- Ditutup Pada & Oleh -->
              <td class="py-2.5 px-3 whitespace-nowrap text-gray-600">
                <template v-if="item.closed_at">
                  <div class="font-medium text-gray-800">
                    {{ item.closed_by?.name || 'Sistem' }}
                  </div>
                  <div class="text-[10px] text-gray-400">
                    {{ formatTimestamp(item.closed_at) }}
                  </div>
                </template>
                <span
                  v-else
                  class="text-gray-400 italic"
                >-</span>
              </td>

              <!-- Jejak Buka Kembali -->
              <td class="py-2.5 px-3 max-w-xs truncate text-gray-600">
                <template v-if="item.reopened_at">
                  <div class="text-[11px] font-semibold text-amber-700 flex items-center gap-1">
                    <span>Re-open oleh {{ item.reopened_by?.name || 'User' }}</span>
                  </div>
                  <div
                    class="text-[10px] text-gray-400 truncate"
                    :title="item.reopen_reason"
                  >
                    "{{ item.reopen_reason }}"
                  </div>
                </template>
                <span
                  v-else
                  class="text-gray-400 italic"
                >-</span>
              </td>

              <!-- Saldo Fisik -->
              <td class="py-2.5 px-3 whitespace-nowrap text-right font-medium text-gray-700">
                {{ item.snapshots_count > 0 ? `${formatQuantity(item.total_closing_qty)} unit` : '-' }}
              </td>

              <!-- Valuasi Persediaan -->
              <td
                class="py-2.5 px-3 whitespace-nowrap text-right font-bold"
                :class="item.total_valuation > 0 ? 'text-emerald-700' : 'text-gray-400'"
              >
                {{ item.snapshots_count > 0 ? formatRupiah(item.total_valuation) : '-' }}
              </td>

              <!-- Aksi -->
              <td class="py-2.5 px-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- Lihat Snapshot -->
                  <button
                    v-if="item.snapshots_count > 0"
                    type="button"
                    class="p-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-md transition-colors cursor-pointer"
                    title="Lihat Rincian Snapshot Saldo & Cetak Berita Acara"
                    @click="openSnapshotModal(item)"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>
                  </button>

                  <!-- Tutup Buku -->
                  <button
                    v-if="item.status === 'OPEN' && canClosePeriod"
                    type="button"
                    class="p-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-md transition-colors cursor-pointer"
                    title="Tutup Buku Periode Ini"
                    @click="openCloseModal(item.year, item.month)"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                  </button>

                  <!-- Buka Kembali -->
                  <button
                    v-if="item.status === 'CLOSED' && canReopenPeriod"
                    type="button"
                    class="p-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-md transition-colors cursor-pointer"
                    title="Buka Kembali Periode (Re-Open)"
                    @click="openReopenModal(item)"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"
                      />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 4. MODAL TUTUP BUKU -->
    <div
      v-if="showCloseModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-lg w-full overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-rose-50/50">
          <div class="flex items-center gap-2 text-rose-700 font-bold text-sm">
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
              />
            </svg>
            <span>Konfirmasi Tutup Buku Bulanan</span>
          </div>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 cursor-pointer"
            @click="showCloseModal = false"
          >
            &times;
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4 text-xs">
          <!-- Selection Grid -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-gray-700 font-semibold mb-1">Tahun</label>
              <select
                v-model.number="closeForm.year"
                class="w-full text-xs rounded-lg border border-gray-200 px-3 py-2 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                @change="checkTargetPending"
              >
                <option
                  v-for="y in yearOptions"
                  :key="y"
                  :value="y"
                >
                  {{ y }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-1">Bulan</label>
              <select
                v-model.number="closeForm.month"
                class="w-full text-xs rounded-lg border border-gray-200 px-3 py-2 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                @change="checkTargetPending"
              >
                <option
                  v-for="(name, idx) in monthNames"
                  :key="idx + 1"
                  :value="idx + 1"
                >
                  {{ name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Pre-closing check Warning -->
          <div
            v-if="modalPendingDocs.length > 0"
            class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-amber-900 space-y-1.5"
          >
            <div class="font-bold flex items-center gap-1.5 text-amber-800">
              <svg
                class="w-4 h-4 text-amber-600 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
              <span>Perhatian: Transaksi DRAFT Ditemukan</span>
            </div>
            <p class="text-[11px] text-amber-700">
              Terdapat dokumen transaksi pada bulan ini yang belum diposting:
            </p>
            <ul class="list-disc list-inside text-[11px] text-amber-800 space-y-0.5 pl-1 font-medium">
              <li
                v-for="(doc, i) in modalPendingDocs"
                :key="i"
              >
                {{ doc }}
              </li>
            </ul>
            <div class="pt-2 border-t border-amber-200">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  v-model="closeForm.force"
                  type="checkbox"
                  class="rounded text-rose-600 focus:ring-rose-500 border-amber-300"
                >
                <span class="text-[11px] font-bold text-amber-900">
                  Tetap lanjutkan tutup buku (Abaikan peringatan draft)
                </span>
              </label>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-gray-700 font-semibold mb-1">Catatan Penutupan Buku (Opsional)</label>
            <textarea
              v-model="closeForm.notes"
              rows="2"
              placeholder="Contoh: Tutup buku persediaan bulanan per cut-off akhir bulan."
              class="w-full text-xs rounded-lg border border-gray-200 p-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
            />
          </div>

          <!-- Info Box -->
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-600 text-[11px] space-y-1">
            <div class="font-bold text-gray-800">
              Dampak Penutupan Buku:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-gray-600 pl-1">
              <li>Seluruh transaksi persediaan pada bulan bersangkutan akan <strong>dikunci otomatis</strong>.</li>
              <li>Sistem menolak penyimpanan atau perubahan dokumen transaksi dengan tanggal mundur.</li>
              <li>Posisi saldo akhir dan nilai persediaan per produk/lokasi akan dibekukan permanen.</li>
            </ul>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
            @click="showCloseModal = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
            :disabled="actionLoading || (modalPendingDocs.length > 0 && !closeForm.force)"
            @click="submitClosePeriod"
          >
            <svg
              v-if="actionLoading"
              class="w-3.5 h-3.5 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"
              />
            </svg>
            <span>Eksekusi Tutup Buku</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 5. MODAL BUKA KEMBALI (RE-OPEN) -->
    <div
      v-if="showReopenModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-md w-full overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-amber-50/50">
          <div class="flex items-center gap-2 text-amber-800 font-bold text-sm">
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"
              />
            </svg>
            <span>Buka Kembali Periode (Re-Open)</span>
          </div>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 cursor-pointer"
            @click="showReopenModal = false"
          >
            &times;
          </button>
        </div>

        <!-- Body -->
        <div class="p-5 space-y-3 text-xs">
          <p class="text-gray-700">
            Anda akan membuka kembali penguncian periode
            <strong class="text-gray-900">{{ selectedPeriod?.month_name }}</strong>.
          </p>

          <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-800">
            <strong>Penting:</strong> Tindakan ini akan dicatat dalam audit trail. Alasan pembukaan kembali wajib diisi dengan jelas.
          </div>

          <div>
            <label class="block text-gray-700 font-semibold mb-1">
              Alasan Pembukaan Kembali <span class="text-rose-500">*</span>
            </label>
            <textarea
              v-model="reopenForm.reopen_reason"
              rows="3"
              placeholder="Contoh: Penyesuaian koreksi faktur penerimaan yang tertinggal oleh Finance."
              class="w-full text-xs rounded-lg border border-gray-200 p-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
            />
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
            @click="showReopenModal = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-xs font-semibold text-white shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
            :disabled="actionLoading || reopenForm.reopen_reason.trim().length < 5"
            @click="submitReopenPeriod"
          >
            <svg
              v-if="actionLoading"
              class="w-3.5 h-3.5 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"
              />
            </svg>
            <span>Konfirmasi Buka Kembali</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 6. MODAL RINCIAN SNAPSHOT SALDO & CETAK BERITA ACARA -->
    <div
      v-if="showSnapshotModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Header -->
        <div class="px-5 py-3.5 border-b border-gray-200 flex items-center justify-between bg-gray-50">
          <div>
            <div class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span>Rincian Saldo Akhir (Snapshot): {{ snapshotPeriod?.month_name }}</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                STATUS: CLOSED
              </span>
            </div>
            <p class="text-[11px] text-gray-500 mt-0.5">
              Cut-off: {{ formatDateIndo(snapshotPeriod?.end_date) }} &bull; Ditutup oleh: {{ snapshotPeriod?.closed_by || 'Sistem' }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <!-- Tombol Cetak Berita Acara -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors cursor-pointer"
              @click="printBeritaAcara"
            >
              <svg
                class="w-3.5 h-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                />
              </svg>
              <span>Cetak Berita Acara</span>
            </button>

            <button
              type="button"
              class="text-gray-400 hover:text-gray-600 p-1 cursor-pointer"
              @click="showSnapshotModal = false"
            >
              &times;
            </button>
          </div>
        </div>

        <!-- Filter & Summary Toolbar inside Modal -->
        <div class="p-4 border-b border-gray-100 space-y-3 bg-white">
          <!-- Summary Badges -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <div class="bg-gray-50 rounded-lg p-2 border border-gray-200">
              <div class="text-[10px] text-gray-500 uppercase font-semibold">
                Total Item/SKU
              </div>
              <div class="text-xs font-bold text-gray-900 mt-0.5">
                {{ snapshotSummary.total_items }} SKU
              </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 border border-gray-200">
              <div class="text-[10px] text-gray-500 uppercase font-semibold">
                Total Saldo Fisik
              </div>
              <div class="text-xs font-bold text-gray-900 mt-0.5">
                {{ formatQuantity(snapshotSummary.total_closing_qty) }} unit
              </div>
            </div>
            <div class="bg-emerald-50 rounded-lg p-2 border border-emerald-200">
              <div class="text-[10px] text-emerald-700 uppercase font-semibold">
                Total Nilai Persediaan
              </div>
              <div class="text-xs font-bold text-emerald-800 mt-0.5">
                {{ formatRupiah(snapshotSummary.total_valuation) }}
              </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 border border-gray-200">
              <div class="text-[10px] text-gray-500 uppercase font-semibold">
                Total Mutasi Bersih
              </div>
              <div class="text-xs font-bold text-gray-800 mt-0.5">
                +{{ formatQuantity(snapshotSummary.total_in_qty) }} / -{{ formatQuantity(snapshotSummary.total_out_qty) }}
              </div>
            </div>
          </div>

          <!-- Search & Filter Controls -->
          <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="flex-1 w-full">
              <input
                v-model="snapshotFilter.search"
                type="text"
                placeholder="Cari SKU atau nama produk..."
                class="w-full text-xs rounded-lg border border-gray-200 px-3 py-1.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                @input="debounceSnapshotFetch"
              >
            </div>
            <div class="w-full sm:w-48">
              <select
                v-model="snapshotFilter.condition"
                class="w-full text-xs rounded-lg border border-gray-200 px-3 py-1.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                @change="fetchSnapshots(1)"
              >
                <option value="">
                  Semua Kondisi
                </option>
                <option value="GOOD">
                  Kondisi Baik (GOOD)
                </option>
                <option value="DEFECTIVE">
                  Kondisi Rusak (DEFECTIVE)
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Snapshot Table Content (Scrollable) -->
        <div class="flex-1 overflow-y-auto overflow-x-auto p-4 custom-scrollbar">
          <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-50 text-gray-600 font-semibold sticky top-0 z-10 shadow-2xs">
              <tr>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-left"
                >
                  No
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-left"
                >
                  Produk / SKU
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-left"
                >
                  Lokasi Stok
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-center"
                >
                  Kondisi
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Saldo Awal
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Masuk
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Keluar
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Penyesuaian
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Saldo Akhir
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Harga Satuan
                </th>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-right"
                >
                  Total Nilai
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-if="snapshotLoading"
                class="text-center"
              >
                <td
                  colspan="11"
                  class="py-8 text-gray-400"
                >
                  <div class="flex items-center justify-center gap-2">
                    <svg
                      class="w-4 h-4 animate-spin text-rose-600"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                      />
                      <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v8H4z"
                      />
                    </svg>
                    <span>Memuat rincian snapshot...</span>
                  </div>
                </td>
              </tr>

              <tr
                v-else-if="snapshotList.length === 0"
                class="text-center"
              >
                <td
                  colspan="11"
                  class="py-8 text-gray-400"
                >
                  Tidak ada data snapshot untuk filter yang dipilih.
                </td>
              </tr>

              <tr
                v-for="(row, idx) in snapshotList"
                :key="row.id"
                class="hover:bg-gray-50/80 transition-colors"
              >
                <td class="py-2 px-2.5 text-gray-400 font-mono text-[10px]">
                  {{ (snapshotPagination.current_page - 1) * snapshotPagination.per_page + idx + 1 }}
                </td>
                <td class="py-2 px-2.5">
                  <div class="font-bold text-gray-900">
                    {{ row.product?.name || 'Item' }}
                  </div>
                  <div class="text-[10px] text-gray-400 font-mono flex items-center gap-1">
                    <span>{{ row.product?.sku }}</span>
                    <span v-if="row.product?.barcode">&bull; {{ row.product?.barcode }}</span>
                  </div>
                </td>
                <td class="py-2 px-2.5 text-gray-700">
                  <div class="font-medium">
                    {{ row.location?.name }}
                  </div>
                  <div class="text-[10px] text-gray-400">
                    {{ row.location?.type }}
                  </div>
                </td>
                <td class="py-2 px-2.5 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="row.condition === 'GOOD' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                  >
                    {{ row.condition === 'GOOD' ? 'BAIK' : 'RUSAK' }}
                  </span>
                </td>
                <td class="py-2 px-2.5 text-right font-mono text-gray-600">
                  {{ formatQuantity(row.opening_balance) }}
                </td>
                <td class="py-2 px-2.5 text-right font-mono text-emerald-600">
                  +{{ formatQuantity(row.total_in) }}
                </td>
                <td class="py-2 px-2.5 text-right font-mono text-rose-600">
                  -{{ formatQuantity(row.total_out) }}
                </td>
                <td
                  class="py-2 px-2.5 text-right font-mono"
                  :class="row.total_adjustment >= 0 ? 'text-gray-600' : 'text-amber-600'"
                >
                  {{ row.total_adjustment > 0 ? '+' : '' }}{{ formatQuantity(row.total_adjustment) }}
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-bold text-gray-900 bg-gray-50/50">
                  {{ formatQuantity(row.closing_balance) }}
                </td>
                <td class="py-2 px-2.5 text-right text-gray-600">
                  {{ formatRupiah(row.unit_price) }}
                </td>
                <td class="py-2 px-2.5 text-right font-bold text-emerald-700">
                  {{ formatRupiah(row.total_value) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Modal Footer with Pagination -->
        <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs">
          <div class="text-gray-500 text-[11px]">
            Halaman {{ snapshotPagination.current_page }} dari {{ snapshotPagination.last_page }} (Total {{ snapshotPagination.total }} baris)
          </div>
          <div class="flex items-center gap-1">
            <button
              type="button"
              class="px-2.5 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              :disabled="snapshotPagination.current_page <= 1"
              @click="fetchSnapshots(snapshotPagination.current_page - 1)"
            >
              &lsaquo; Sebelumnya
            </button>
            <button
              type="button"
              class="px-2.5 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              :disabled="snapshotPagination.current_page >= snapshotPagination.last_page"
              @click="fetchSnapshots(snapshotPagination.current_page + 1)"
            >
              Selanjutnya &rsaquo;
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import { monthEndApi } from '../api/month_end_api';
import { formatRupiah, formatQuantity, formatTimestamp } from '@/shared/utils/formatters';

const authStore = useAuthStore();

// Permissions
const canClosePeriod = computed(() => authStore.hasPermission('month_end.close'));
const canReopenPeriod = computed(() => authStore.hasPermission('month_end.reopen'));

// Data states
const periods = ref([]);
const loading = ref(false);
const actionLoading = ref(false);
const searchQuery = ref('');
const feedbackMessage = ref('');
const feedbackType = ref('success');

// Pre-closing checks
const pendingDraftsCount = ref(0);
const pendingDraftsSummary = ref('');
const modalPendingDocs = ref([]);

// Modals
const showCloseModal = ref(false);
const showReopenModal = ref(false);
const showSnapshotModal = ref(false);

const selectedPeriod = ref(null);
const snapshotPeriod = ref(null);
const snapshotList = ref([]);
const snapshotLoading = ref(false);
const snapshotSummary = ref({
  total_items: 0,
  total_closing_qty: 0,
  total_valuation: 0,
  total_in_qty: 0,
  total_out_qty: 0,
});
const snapshotPagination = ref({
  current_page: 1,
  per_page: 50,
  total: 0,
  last_page: 1,
});
const snapshotFilter = ref({
  search: '',
  condition: '',
});

// Close Form
const now = new Date();
const closeForm = ref({
  year: now.getFullYear(),
  month: now.getMonth() + 1,
  notes: '',
  force: false,
});

// Reopen Form
const reopenForm = ref({
  reopen_reason: '',
});

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const yearOptions = computed(() => {
  const currentY = now.getFullYear();
  return [currentY - 1, currentY, currentY + 1];
});

const currentMonthLabel = computed(() => {
  return `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
});

const currentMonthKey = computed(() => {
  const m = String(now.getMonth() + 1).padStart(2, '0');
  return `${now.getFullYear()}-${m}`;
});

const currentMonthPeriod = computed(() => {
  return periods.value.find((p) => p.period_key === currentMonthKey.value) || null;
});

const lastClosedPeriod = computed(() => {
  return periods.value.find((p) => p.status === 'CLOSED') || null;
});

const filteredPeriods = computed(() => {
  if (!searchQuery.value) return periods.value;
  const q = searchQuery.value.toLowerCase();
  return periods.value.filter((p) =>
    (p.month_name && p.month_name.toLowerCase().includes(q)) ||
    (p.period_key && p.period_key.toLowerCase().includes(q))
  );
});

// Methods
async function fetchPeriods() {
  loading.value = true;
  try {
    const res = await monthEndApi.getPeriods();
    if (res.data?.success) {
      periods.value = res.data.data || [];
    }
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Gagal memuat daftar periode.', 'error');
  } finally {
    loading.value = false;
  }
}

async function fetchCurrentMonthSummary() {
  try {
    const res = await monthEndApi.getSummary({
      year: now.getFullYear(),
      month: now.getMonth() + 1,
    });
    if (res.data?.success) {
      const docs = res.data.data?.pending_documents || [];
      pendingDraftsCount.value = docs.length;
      pendingDraftsSummary.value = docs.join(', ');
    }
  } catch {
    // Ignore non-fatal pre-check error
  }
}

async function checkTargetPending() {
  modalPendingDocs.value = [];
  try {
    const res = await monthEndApi.getSummary({
      year: closeForm.value.year,
      month: closeForm.value.month,
    });
    if (res.data?.success) {
      modalPendingDocs.value = res.data.data?.pending_documents || [];
    }
  } catch {
    modalPendingDocs.value = [];
  }
}

function openCloseModal(year = null, month = null) {
  closeForm.value = {
    year: year || now.getFullYear(),
    month: month || (now.getMonth() + 1),
    notes: '',
    force: false,
  };
  showCloseModal.value = true;
  checkTargetPending();
}

async function submitClosePeriod() {
  actionLoading.value = true;
  try {
    const res = await monthEndApi.closePeriod(closeForm.value);
    if (res.data?.success) {
      showFeedback(res.data.message || 'Tutup buku berhasil dieksekusi.');
      showCloseModal.value = false;
      await fetchPeriods();
      await fetchCurrentMonthSummary();
    }
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Gagal mengeksekusi tutup buku.', 'error');
  } finally {
    actionLoading.value = false;
  }
}

function openReopenModal(period) {
  selectedPeriod.value = period;
  reopenForm.value = { reopen_reason: '' };
  showReopenModal.value = true;
}

async function submitReopenPeriod() {
  if (!selectedPeriod.value) return;
  actionLoading.value = true;
  try {
    const res = await monthEndApi.reopenPeriod(selectedPeriod.value.id, reopenForm.value);
    if (res.data?.success) {
      showFeedback(res.data.message || 'Periode berhasil dibuka kembali.');
      showReopenModal.value = false;
      await fetchPeriods();
      await fetchCurrentMonthSummary();
    }
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Gagal membuka kembali periode.', 'error');
  } finally {
    actionLoading.value = false;
  }
}

// Snapshot modal logic
function openSnapshotModal(period) {
  snapshotPeriod.value = period;
  snapshotFilter.value = { search: '', condition: '' };
  showSnapshotModal.value = true;
  fetchSnapshots(1);
}

let debounceTimer = null;
function debounceSnapshotFetch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchSnapshots(1);
  }, 300);
}

async function fetchSnapshots(page = 1) {
  if (!snapshotPeriod.value) return;
  snapshotLoading.value = true;
  try {
    const params = {
      page,
      per_page: snapshotPagination.value.per_page,
      search: snapshotFilter.value.search || undefined,
      condition: snapshotFilter.value.condition || undefined,
    };
    const res = await monthEndApi.getSnapshots(snapshotPeriod.value.id, params);
    if (res.data?.success) {
      const payload = res.data.data;
      snapshotList.value = payload.snapshots || [];
      snapshotSummary.value = payload.summary || {};
      snapshotPagination.value = payload.pagination || snapshotPagination.value;
    }
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Gagal memuat snapshot saldo.', 'error');
  } finally {
    snapshotLoading.value = false;
  }
}

function printBeritaAcara() {
  if (!snapshotPeriod.value) return;
  const p = snapshotPeriod.value;
  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    alert('Popup terblokir oleh browser. Izinkan popup untuk mencetak berita acara.');
    return;
  }

  const rowsHtml = snapshotList.value.map((r, i) => `
    <tr>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:center;">${i + 1}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;">
        <strong>${r.product?.name || '-'}</strong><br/>
        <small style="color:#666;">SKU: ${r.product?.sku || '-'}</small>
      </td>
      <td style="border:1px solid #ccc;padding:4px 6px;">${r.location?.name || '-'}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:center;">${r.condition === 'GOOD' ? 'BAIK' : 'RUSAK'}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:right;">${formatQuantity(r.opening_balance)}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:right;">+${formatQuantity(r.total_in)}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:right;">-${formatQuantity(r.total_out)}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:right;"><strong>${formatQuantity(r.closing_balance)}</strong></td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:right;">${formatRupiah(r.unit_price)}</td>
      <td style="border:1px solid #ccc;padding:4px 6px;text-align:right;"><strong>${formatRupiah(r.total_value)}</strong></td>
    </tr>
  `).join('');

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title>Berita Acara Tutup Buku - ${p.month_name}</title>
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 12px; color: #111; margin: 20px; }
          .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
          .title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 0; }
          .subtitle { font-size: 12px; color: #555; margin-top: 4px; }
          .meta-grid { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px; }
          table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
          th { background: #f0f0f0; border: 1px solid #999; padding: 6px; }
          .summary-box { display: flex; justify-content: space-between; background: #fafafa; border: 1px solid #ddd; padding: 10px; margin-bottom: 25px; }
          .signature-grid { display: flex; justify-content: space-around; margin-top: 40px; text-align: center; }
          .sig-line { margin-top: 60px; border-top: 1px solid #333; width: 180px; }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="title">BERITA ACARA TUTUP BUKU BULANAN PERSEDIAAN</div>
          <div class="subtitle">Sistem Manajemen Persediaan EDP (StockEdp)</div>
        </div>

        <div class="meta-grid">
          <div>
            <div><strong>Periode Pembukuan:</strong> ${p.month_name} (${p.period_key})</div>
            <div><strong>Tanggal Cut-Off:</strong> ${p.end_date}</div>
          </div>
          <div style="text-align:right;">
            <div><strong>Petugas Penutup:</strong> ${p.closed_by || 'Supervisor / Admin'}</div>
            <div><strong>Waktu Penutupan:</strong> ${formatTimestamp(p.closed_at)}</div>
          </div>
        </div>

        <div class="summary-box">
          <div><strong>Total SKU:</strong> ${snapshotSummary.value.total_items} SKU</div>
          <div><strong>Total Kuantitas Fisik:</strong> ${formatQuantity(snapshotSummary.value.total_closing_qty)} Unit</div>
          <div><strong>Total Nilai Persediaan:</strong> ${formatRupiah(snapshotSummary.value.total_valuation)}</div>
        </div>

        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Produk & SKU</th>
              <th>Lokasi</th>
              <th>Kondisi</th>
              <th>Awal</th>
              <th>Masuk</th>
              <th>Keluar</th>
              <th>Saldo Akhir</th>
              <th>Harga</th>
              <th>Total Nilai</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>

        <div class="signature-grid">
          <div>
            <div>Dibuat Oleh,</div>
            <div class="sig-line"></div>
            <div>Petugas Gudang / EDP</div>
          </div>
          <div>
            <div>Diketahui & Disetujui Oleh,</div>
            <div class="sig-line"></div>
            <div>Supervisor Persediaan / Admin</div>
          </div>
        </div>

        <script>
          window.onload = function() {
            window.print();
          };
        ${'<'}/script>
      </body>
    </html>
  `);
  printWindow.document.close();
}

function formatDateIndo(dateStr) {
  if (!dateStr) return '-';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function showFeedback(msg, type = 'success') {
  feedbackMessage.value = msg;
  feedbackType.value = type;
  setTimeout(() => {
    if (feedbackMessage.value === msg) {
      feedbackMessage.value = '';
    }
  }, 6000);
}

onMounted(() => {
  fetchPeriods();
  fetchCurrentMonthSummary();
});
</script>
