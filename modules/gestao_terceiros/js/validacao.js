function toggleAgenciaNome() {
    var agenciaSelect = document.getElementById('tem_agencia');
    var agenciaNomeContainer = document.getElementById('agenciaNomeContainer');
    var nomeAgenciaInput = document.getElementById('nome_agencia');

    if (agenciaSelect.value === 'Sim') {
        agenciaNomeContainer.style.display = 'block';
        nomeAgenciaInput.setAttribute('required', 'required');
    } else {
        agenciaNomeContainer.style.display = 'none';
        nomeAgenciaInput.removeAttribute('required');
    }
}