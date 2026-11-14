<div class="modal fade" id="delete-indicator-modal" tabindex="-1" aria-labelledby="delete-indicator-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="delete-indicator-modal-title">Excluir indicador</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    Tem certeza de que deseja excluir <strong data-delete-indicator-name></strong>?
                </p>
                <p class="text-secondary small mb-0">
                    Todos os valores vinculados a este indicador também serão removidos. O outro indicador não será afetado.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="#" data-delete-indicator-form>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir indicador</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.getElementById('delete-indicator-modal').addEventListener('show.bs.modal', event => {
            const trigger = event.relatedTarget;
            const modal = event.currentTarget;

            modal.querySelector('[data-delete-indicator-name]').textContent = trigger.dataset.deleteName;
            modal.querySelector('[data-delete-indicator-form]').action = trigger.dataset.deleteUrl;
        });
    </script>
@endpush
