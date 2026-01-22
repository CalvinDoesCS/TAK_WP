const getFormattedFileName = (value: string) => {
  const fileName = value.trim();
  const firstLine =
    fileName.length > 15
      ? fileName.slice(0, 15).search(" ") !== -1
        ? fileName.slice(0, fileName.slice(0, 15).lastIndexOf(" "))
        : fileName.slice(0, 15)
      : fileName;
  const secondLine =
    fileName.length > 15
      ? fileName.slice(0, 15).search(" ") !== -1
        ? fileName.slice(
            fileName.slice(0, 15).lastIndexOf(" "),
            fileName.length
          )
        : fileName.slice(15)
      : "";

  return {
    firstLine,
    secondLine:
      secondLine.length > 15
        ? secondLine.slice(0, 5) + "..." + secondLine.slice(-5).trim()
        : secondLine,
  };
};

export { getFormattedFileName };
