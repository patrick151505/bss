{{-- Confirm before deleting an item (items list and manage page). --}}
@push('inline-scripts')
<script>
function swalDeleteItem(id, name) {
    Swal.fire({
        title: 'Delete this item?',
        html: `<p class="text-sm"><b>${String(name).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))}</b> and its stock history will be removed.<br>Items already used in a release, rental or reservation can't be deleted — untick "Active" instead.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important'),
    }).then(r => { if (r.isConfirmed) document.getElementById('delete-item-' + id).submit(); });
}
</script>
@endpush
