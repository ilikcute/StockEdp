<template>
  <div class="space-y-3">
    <!-- TOP Header & Action Strip Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <router-link
          to="/inventory/transfers"
          class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 px-2.5 py-1.5 rounded-lg border border-gray-200 transition-colors"
        >
          &larr; Kembali
        </router-link>

        <div class="h-4 w-px bg-gray-200" />

        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 tracking-tight font-mono">
              {{ transfer?.transfer_number || 'Detail Transfer Stok' }}
            </h1>
            <span
              v-if="transfer"
              class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-800': transfer.status === 'DRAFT',
                'bg-blue-100 text-blue-800': transfer.status === 'IN_TRANSIT',
                'bg-green-100 text-green-800': transfer.status === 'RECEIVED',
                'bg-orange-100 text-orange-800': transfer.status === 'DISCREPANCY',
                'bg-gray-100 text-gray-800': transfer.status === 'CANCELED'
              }"
            >
              {{ ({ DRAFT: 'Draft', 'IN_TRANSIT': 'Dikirim (In-Transit)', RECEIVED: 'Diterima', DISCREPANCY: 'Selisih (Discrepancy)', CANCELED: 'Dibatalkan' })[transfer.status] || transfer.status }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500">
            Rincian dokumen perpindahan barang antar lokasi.
          </p>
        </div>
      </div>

      <div
        v-if="transfer"
        class="flex items-center gap-2 flex-wrap"
      >
        <BasePrintButton
          label="Cetak Surat Jalan"
          @click="handlePrint"
        />
        <router-link
          v-if="transfer.status === 'DRAFT' && hasPermission('stock_transfers.update')"
          :to="`/inventory/transfers/${transfer.id}/edit`"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="transfer.status === 'DRAFT' && hasPermission('stock_transfers.cancel')"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-100 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('cancel')"
        >
          Batalkan Draft
        </button>

        <button
          v-if="transfer.status === 'DRAFT' && hasPermission('stock_transfers.send')"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-blue-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('send')"
        >
          Kirim Barang (Send)
        </button>

        <button
          v-if="transfer.status === 'IN_TRANSIT' && hasPermission('stock_transfers.receive')"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('receive')"
        >
          Terima Barang (Receive)
        </button>
      </div>
    </div>

    <div
      v-if="store.loadingDetail && !transfer"
      class="p-8 text-center text-xs text-gray-500"
    >
      Memuat data transfer...
    </div>

    <div
      v-else-if="transfer"
      class="space-y-3"
    >
      <div
        v-if="store.error"
        class="rounded-xl border border-rose-200 bg-rose-50 p-3"
      >
        <p class="text-xs font-medium text-rose-800">
          {{ store.error }}
        </p>
      </div>

      <!-- Compact Metadata Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-2xs">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Jenis Transfer</span>
            <span class="font-semibold text-gray-800">{{ transfer.transfer_type === 'RETURN' ? 'Retur ke Gudang' : 'Transfer Stok' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Lokasi Asal</span>
            <span class="font-semibold text-gray-800">{{ transfer.origin_location_name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Lokasi Tujuan</span>
            <span class="font-semibold text-gray-800">{{ transfer.destination_location_name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Tanggal Transfer</span>
            <span class="font-semibold text-gray-800">{{ transfer.transfer_date }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Dibuat Oleh</span>
            <span class="font-semibold text-gray-800">{{ transfer.created_by || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Catatan</span>
            <span class="text-gray-700 truncate block">{{ transfer.notes || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Items Section -->
      <div class="bg-white shadow-2xs border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 sm:px-4 border-b border-gray-100">
          <h3 class="text-xs font-semibold leading-5 text-gray-900">
            Daftar Barang Ditransfer
          </h3>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
              <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
                <th
                  scope="col"
                  class="py-1.5 px-1.5 w-8 text-center"
                >
                  No.
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 whitespace-nowrap"
                >
                  Produk
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 whitespace-nowrap"
                >
                  SKU
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 whitespace-nowrap"
                >
                  Satuan (Unit)
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Harga Satuan
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-center whitespace-nowrap"
                >
                  Kondisi
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Jumlah (Quantity)
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Diterima
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Nilai Transfer
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-for="(item, index) in transfer.items"
                :key="item.id"
                class="hover:bg-gray-50/80 transition-colors"
              >
                <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
                  {{ index + 1 }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
                  {{ item.product?.name || item.product_name || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[10px] text-gray-400 font-mono whitespace-nowrap">
                  {{ item.product?.sku || item.product_sku || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
                  {{ item.product?.unit?.symbol || item.product?.unit?.name || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-700 whitespace-nowrap">
                  {{ formatRupiah(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0) }}
                </td>
                <td class="py-1.5 px-2 text-center whitespace-nowrap">
                  <span
                    class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded inline-flex items-center"
                    :class="(item.condition || 'GOOD') === 'DEFECTIVE'
                      ? 'bg-rose-50 text-rose-700 border border-rose-200'
                      : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                  >
                    {{ (item.condition || 'GOOD') === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
                  </span>
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 whitespace-nowrap">
                  {{ formatQuantity(item.quantity, false) }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 whitespace-nowrap">
                  {{ item.received_quantity != null ? formatQuantity(item.received_quantity, false) : '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right font-medium text-gray-900 whitespace-nowrap">
                  {{ formatRupiah(item.subtotal ?? (Number(item.quantity || 0) * Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0))) }}
                </td>
              </tr>
            </tbody>
            <tfoot
              v-if="transfer?.items?.length"
              class="border-t border-gray-200 bg-gray-50/90 text-xs font-medium"
            >
              <tr class="text-[11px]">
                <td
                  colspan="8"
                  class="py-1.5 px-2 text-right text-gray-700 font-semibold"
                >
                  Grand Total Nilai Transfer:
                </td>
                <td class="py-1.5 px-2 text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
                  {{ formatRupiah(grandTotal) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Dialog Confirmation -->
    <div
      v-if="confirmActionType"
      class="fixed inset-0 z-50 overflow-y-auto flex min-h-full items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="fixed inset-0"
        aria-hidden="true"
        @click="closeConfirmModal"
      />

      <!-- Mode Receive: Compact High-Density Table Modal -->
      <div
        v-if="confirmActionType === 'receive'"
        class="relative w-full max-w-4xl transform overflow-hidden rounded-xl bg-white text-left shadow-2xl border border-gray-100 transition-all z-10 flex flex-col max-h-[90vh]"
      >
        <!-- Modal Header -->
        <div class="px-4 py-3 bg-emerald-50/80 border-b border-emerald-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
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
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3
                  id="modal-title"
                  class="text-sm font-bold text-gray-900 leading-tight"
                >
                  Konfirmasi Penerimaan Barang Transfer
                </h3>
                <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
                  {{ transfer?.transfer_number }}
                </span>
              </div>
              <p class="text-[11px] text-gray-500 mt-0.5">
                Rute: <strong class="text-gray-700">{{ transfer?.origin_location_name }}</strong> &rarr; <strong class="text-gray-700">{{ transfer?.destination_location_name }}</strong>
              </p>
            </div>
          </div>
          <button
            type="button"
            class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
            @click="closeConfirmModal"
          >
            <span class="text-lg leading-none">&times;</span>
          </button>
        </div>

        <!-- Modal Body (Scrollable Compact Content) -->
        <div class="p-3 sm:p-4 overflow-y-auto space-y-2.5 custom-scrollbar">
          <!-- Toolbar & Instructions -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="text-gray-600 text-[11px]">
              Verifikasi fisik barang yang tiba di gudang tujuan. Selisih jumlah kirim dan terima otomatis dicatat sebagai <em>Discrepancy</em>.
            </div>
            <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
              <button
                type="button"
                class="px-2 py-1 text-[11px] font-semibold rounded bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer"
                title="Isi otomatis semua jumlah terima sesuai jumlah kirim"
                @click="fillAllReceived"
              >
                ✓ Isi Sesuai Kiriman
              </button>
              <button
                type="button"
                class="px-2 py-1 text-[11px] font-semibold rounded bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer"
                title="Reset semua jumlah terima ke 0"
                @click="clearAllReceived"
              >
                Nol-kan
              </button>
            </div>
          </div>

          <!-- Items Table -->
          <div class="border border-gray-200 rounded-lg overflow-x-auto shadow-2xs">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
              <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider text-[10px]">
                <tr>
                  <th
                    scope="col"
                    class="py-2 px-2 text-center w-8"
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
                    class="py-2 px-2 text-center"
                  >
                    Kondisi
                  </th>
                  <th
                    scope="col"
                    class="py-2 px-2.5 text-right"
                  >
                    Qty Kirim
                  </th>
                  <th
                    scope="col"
                    class="py-2 px-2.5 text-center w-28"
                  >
                    Qty Diterima
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
                  <th
                    scope="col"
                    class="py-2 px-2 text-center"
                  >
                    Status
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr
                  v-for="(item, idx) in transfer?.items || []"
                  :key="item.id"
                  class="hover:bg-emerald-50/20 transition-colors"
                >
                  <!-- No -->
                  <td class="py-1.5 px-2 text-center text-gray-400 font-mono text-[11px]">
                    {{ idx + 1 }}
                  </td>

                  <!-- Produk / SKU -->
                  <td class="py-1.5 px-2.5">
                    <div class="font-medium text-gray-900 text-xs leading-tight">
                      {{ item.product?.name || item.product_name || '-' }}
                    </div>
                    <div class="text-[10px] text-gray-400 font-mono mt-0.5 flex items-center gap-1">
                      <span>{{ item.product?.sku || item.product_sku || '-' }}</span>
                      <span v-if="item.product?.unit?.symbol || item.product?.unit?.name">
                        &bull; {{ item.product?.unit?.symbol || item.product?.unit?.name }}
                      </span>
                    </div>
                  </td>

                  <!-- Kondisi -->
                  <td class="py-1.5 px-2 text-center whitespace-nowrap">
                    <span
                      class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded inline-flex items-center"
                      :class="(item.condition || 'GOOD') === 'DEFECTIVE'
                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                    >
                      {{ (item.condition || 'GOOD') === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
                    </span>
                  </td>

                  <!-- Qty Kirim -->
                  <td class="py-1.5 px-2.5 text-right font-mono text-gray-700 text-xs whitespace-nowrap">
                    {{ formatQuantity(item.quantity, false) }}
                  </td>

                  <!-- Qty Diterima Input -->
                  <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                    <input
                      :id="`received-${item.id}`"
                      v-model.number="receivedQuantities[item.id]"
                      type="number"
                      min="0"
                      step="0.0001"
                      class="w-24 text-right rounded border px-2 py-1 text-xs font-mono font-bold transition-colors focus:outline-none focus:ring-1"
                      :class="Number(receivedQuantities[item.id] || 0) === Number(item.quantity)
                        ? 'border-gray-300 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500'
                        : Number(receivedQuantities[item.id] || 0) < Number(item.quantity)
                          ? 'border-amber-300 bg-amber-50/50 text-amber-900 focus:border-amber-500 focus:ring-amber-500'
                          : 'border-blue-300 bg-blue-50/50 text-blue-900 focus:border-blue-500 focus:ring-blue-500'"
                    >
                  </td>

                  <!-- Harga Satuan -->
                  <td class="py-1.5 px-2.5 text-right font-mono text-gray-600 text-xs whitespace-nowrap">
                    {{ formatRupiah(getItemPrice(item)) }}
                  </td>

                  <!-- Total Nilai Diterima (Quantity * Unit Price) -->
                  <td class="py-1.5 px-2.5 text-right font-mono font-semibold text-emerald-700 text-xs whitespace-nowrap">
                    {{ formatRupiah((Number(receivedQuantities[item.id]) || 0) * getItemPrice(item)) }}
                  </td>

                  <!-- Status / Selisih Badge -->
                  <td class="py-1.5 px-2 text-center whitespace-nowrap">
                    <span
                      v-if="Number(receivedQuantities[item.id] || 0) === Number(item.quantity)"
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      ✓ Cocok
                    </span>
                    <span
                      v-else-if="Number(receivedQuantities[item.id] || 0) < Number(item.quantity)"
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-50 text-amber-700 border border-amber-200"
                    >
                      -{{ formatQuantity(Number(item.quantity) - Number(receivedQuantities[item.id] || 0), false) }}
                    </span>
                    <span
                      v-else
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-50 text-blue-700 border border-blue-200"
                    >
                      +{{ formatQuantity(Number(receivedQuantities[item.id] || 0) - Number(item.quantity), false) }}
                    </span>
                  </td>
                </tr>
              </tbody>

              <!-- Table Footer Grand Total -->
              <tfoot class="bg-gray-50/90 font-bold border-t border-gray-200 text-xs">
                <tr>
                  <td
                    colspan="3"
                    class="py-2 px-2.5 text-left text-gray-600 font-semibold"
                  >
                    Total ({{ transfer?.items?.length || 0 }} Item)
                  </td>
                  <td class="py-2 px-2.5 text-right font-mono text-gray-700 whitespace-nowrap">
                    {{ formatQuantity(totalSentQty, false) }}
                  </td>
                  <td class="py-2 px-2.5 text-center font-mono text-gray-900 whitespace-nowrap">
                    {{ formatQuantity(totalReceivedQty, false) }}
                  </td>
                  <td class="py-2 px-2.5 text-right text-gray-500 font-medium whitespace-nowrap">
                    Nilai Diterima:
                  </td>
                  <td class="py-2 px-2.5 text-right font-mono text-emerald-800 text-xs font-bold whitespace-nowrap">
                    {{ formatRupiah(totalReceivedAmount) }}
                  </td>
                  <td class="py-2 px-2 text-center whitespace-nowrap">
                    <span
                      v-if="discrepancyItemsCount > 0"
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-100 text-amber-800"
                    >
                      {{ discrepancyItemsCount }} Selisih
                    </span>
                    <span
                      v-else
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-emerald-100 text-emerald-800"
                    >
                      100% Sesuai
                    </span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Discrepancy Notice Banner -->
          <div
            v-if="discrepancyItemsCount > 0"
            class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2"
          >
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
            <div class="text-[11px] leading-tight">
              <strong>Peringatan Selisih:</strong> Terdapat {{ discrepancyItemsCount }} item dengan jumlah terima tidak sama dengan jumlah kirim. Transaksi ini akan ditandai dengan status <strong>Discrepancy</strong> pada kartu stok dan riwayat pergerakan.
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
          <div class="text-xs text-gray-500">
            Total Nilai Diterima: <strong class="font-mono text-emerald-700 text-sm font-bold">{{ formatRupiah(totalReceivedAmount) }}</strong>
          </div>
          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs cursor-pointer"
              @click="closeConfirmModal"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="store.loadingAction || totalReceivedQty <= 0"
              class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs disabled:opacity-50 transition-colors cursor-pointer"
              @click="executeAction"
            >
              <span
                v-if="store.loadingAction"
                class="inline-block animate-spin mr-1"
              >⟳</span>
              <span>Konfirmasi & Terima Barang</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Mode Send / Cancel: Clean Compact Modal -->
      <div
        v-else
        class="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white text-left shadow-2xl border border-gray-100 transition-all z-10 p-5"
      >
        <div class="flex items-start gap-3.5">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
            :class="{
              'bg-blue-100 text-blue-600': confirmActionType === 'send',
              'bg-rose-100 text-rose-600': confirmActionType === 'cancel'
            }"
          >
            <span class="text-base font-bold">!</span>
          </div>
          <div class="flex-1 min-w-0">
            <h3
              id="modal-title"
              class="text-sm font-bold text-gray-900 leading-tight"
            >
              Konfirmasi {{ confirmTitle }}
            </h3>
            <p class="mt-1.5 text-xs text-gray-600 leading-relaxed">
              {{ confirmDescription }}
            </p>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs cursor-pointer"
            @click="closeConfirmModal"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="store.loadingAction"
            :class="[
              confirmActionType === 'send' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-rose-600 hover:bg-rose-700',
              'inline-flex items-center px-4 py-1.5 text-xs font-semibold rounded-lg text-white shadow-xs disabled:opacity-50 transition-colors cursor-pointer'
            ]"
            @click="executeAction"
          >
            <span
              v-if="store.loadingAction"
              class="inline-block animate-spin mr-1"
            >⟳</span>
            {{ confirmButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useStockTransferStore } from '../stores/useStockTransferStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { formatQuantity, formatRupiah } from '@/shared/utils/formatters';
import { printStockTransfer } from '@/shared/utils/printDocument';
import BasePrintButton from '@/shared/components/BasePrintButton.vue';

const route = useRoute();
const store = useStockTransferStore();
const authStore = useAuthStore();

const transfer = computed(() => store.currentTransfer);

const handlePrint = () => {
  if (!transfer.value) return;
  printStockTransfer(transfer.value, {
    printedBy: authStore.user?.name || 'Sistem StockEdp',
  });
};

const grandTotal = computed(() => {
  if (!transfer.value?.items) return 0;
  return transfer.value.items.reduce((sum, item) => {
    const qty = Number(item.quantity) || 0;
    const price = Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0);
    return sum + (qty * price);
  }, 0);
});

const confirmActionType = ref(null);
const receivedQuantities = ref({});

const resetReceivedQuantities = () => {
  receivedQuantities.value = {};
  if (transfer.value?.items) {
    transfer.value.items.forEach((item) => {
      receivedQuantities.value[item.id] = Number(item.quantity) || 0;
    });
  }
};

const getItemPrice = (item) => {
  return Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0);
};

const totalSentQty = computed(() => {
  if (!transfer.value?.items) return 0;
  return transfer.value.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
});

const totalReceivedQty = computed(() => {
  if (!transfer.value?.items) return 0;
  return transfer.value.items.reduce((sum, item) => {
    return sum + (Number(receivedQuantities.value[item.id]) || 0);
  }, 0);
});

const totalReceivedAmount = computed(() => {
  if (!transfer.value?.items) return 0;
  return transfer.value.items.reduce((sum, item) => {
    const qty = Number(receivedQuantities.value[item.id]) || 0;
    return sum + (qty * getItemPrice(item));
  }, 0);
});

const discrepancyItemsCount = computed(() => {
  if (!transfer.value?.items) return 0;
  return transfer.value.items.filter((item) => {
    const sent = Number(item.quantity) || 0;
    const received = Number(receivedQuantities.value[item.id]) || 0;
    return Math.abs(sent - received) > 0.00001;
  }).length;
});

const fillAllReceived = () => {
  if (transfer.value?.items) {
    transfer.value.items.forEach((item) => {
      receivedQuantities.value[item.id] = Number(item.quantity) || 0;
    });
  }
};

const clearAllReceived = () => {
  if (transfer.value?.items) {
    transfer.value.items.forEach((item) => {
      receivedQuantities.value[item.id] = 0;
    });
  }
};

const confirmTitle = computed(() => {
  if (confirmActionType.value === 'send') return 'Pengiriman Stok';
  if (confirmActionType.value === 'receive') return 'Penerimaan Stok';
  if (confirmActionType.value === 'cancel') return 'Pembatalan Draft';
  return '';
});

const confirmDescription = computed(() => {
  if (confirmActionType.value === 'send') {
    return `Apakah Anda yakin ingin mengirim dokumen transfer #${transfer.value?.transfer_number}? Stok di lokasi asal (${transfer.value?.origin_location_name}) akan berkurang, dan barang akan berstatus In-Transit.`;
  }
  if (confirmActionType.value === 'receive') {
    return `Apakah Anda yakin ingin menerima dokumen transfer #${transfer.value?.transfer_number}? Stok di lokasi tujuan (${transfer.value?.destination_location_name}) akan bertambah.`;
  }
  if (confirmActionType.value === 'cancel') {
    return `Apakah Anda yakin ingin membatalkan draft transfer #${transfer.value?.transfer_number}? Pembatalan draft tidak memengaruhi saldo stok.`;
  }
  return '';
});

const confirmButtonText = computed(() => {
  if (confirmActionType.value === 'send') return 'Ya, Kirim Barang';
  if (confirmActionType.value === 'receive') return 'Ya, Terima Barang';
  if (confirmActionType.value === 'cancel') return 'Ya, Batalkan Draft';
  return 'Proses';
});

const openConfirmModal = (type) => {
  confirmActionType.value = type;
  if (type === 'receive') {
    resetReceivedQuantities();
  }
};

const closeConfirmModal = () => {
  confirmActionType.value = null;
};

const executeAction = async () => {
  if (!confirmActionType.value || !transfer.value) return;

  const id = transfer.value.id;
  const actionType = confirmActionType.value;
  closeConfirmModal();

  try {
    if (actionType === 'send') {
      await store.sendTransfer(id);
    } else if (actionType === 'receive') {
      const items = Object.entries(receivedQuantities.value).map(([itemId, qty]) => ({
        item_id: Number(itemId),
        received_quantity: Number(qty),
      }));
      await store.receiveTransfer(id, items);
    } else if (actionType === 'cancel') {
      await store.cancelTransfer(id);
    }
  } catch {
    // Handled by Pinia store
  }
};

const hasPermission = (permission) => {
  return authStore.hasPermission(permission);
};

onMounted(() => {
  store.fetchTransferById(route.params.id);
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
