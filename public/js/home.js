async function loadData() {
  try {
    const response = await fetch("../data/data.json");

    if (!response.ok) {
      throw new Error(`Erro ao carregar o ficheiro: ${response.status}`);
    }

    const dataJson = await response.json();

    console.log(dataJson);

    const finance = dataJson.filter((emp) => emp.department === "Finance");
    console.log(finance);
  } catch (erro) {
    console.error("Ocorreu um erro:", erro);
  }
}

loadData();