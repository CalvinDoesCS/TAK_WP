import { useState } from "react";
import { Input } from "@/components/Base/Input";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";

function Main() {
  // Define state with clear data types
  const [display, setDisplay] = useState<string>("0");
  const [previousValue, setPreviousValue] = useState<number | null>(null);
  const [operator, setOperator] = useState<string | null>(null);
  const [waitingForOperand, setWaitingForOperand] = useState<boolean>(false);

  // Function to handle number input
  const handleNumberClick = (value: string) => {
    if (waitingForOperand) {
      setDisplay(value);
      setWaitingForOperand(false);
    } else {
      setDisplay(display === "0" ? value : display + value);
    }
  };

  // Function to handle operator input
  const handleOperatorClick = (nextOperator: string) => {
    const inputValue = parseFloat(display);

    if (previousValue === null) {
      setPreviousValue(inputValue);
    } else if (operator) {
      const currentValue = previousValue || 0;
      const result = calculate(currentValue, inputValue, operator);
      setDisplay(String(result));
      setPreviousValue(result);
    }

    setOperator(nextOperator);
    setWaitingForOperand(true);
  };

  // Basic calculation function
  const calculate = (
    firstOperand: number,
    secondOperand: number,
    operator: string
  ): number => {
    switch (operator) {
      case "+":
        return firstOperand + secondOperand;
      case "-":
        return firstOperand - secondOperand;
      case "x":
        return firstOperand * secondOperand;
      case "÷":
        return firstOperand / secondOperand;
      default:
        return secondOperand;
    }
  };

  // Function to clear the calculator
  const handleClear = () => {
    setDisplay("0");
    setPreviousValue(null);
    setOperator(null);
    setWaitingForOperand(false);
  };

  // Function to toggle between positive/negative
  const handleSignChange = () => {
    setDisplay((prevDisplay) => String(parseFloat(prevDisplay) * -1));
  };

  // Function to convert the number into a percentage
  const handlePercentage = () => {
    setDisplay((prevDisplay) => String(parseFloat(prevDisplay) / 100));
  };

  // Function to compute the result when "=" is pressed
  const handleEqual = () => {
    if (operator && previousValue !== null) {
      const result = calculate(previousValue, parseFloat(display), operator);
      setDisplay(String(result));
      setPreviousValue(null);
      setOperator(null);
      setWaitingForOperand(false);
    }
  };

  return (
    <>
      <Window
        x="center"
        y="10%"
        width={250}
        height={330}
        maxWidth={500}
        maxHeight={330}
      >
        <ControlButtons className="mt-2.5 ml-3" />
        <DraggableHandle className="absolute w-full h-20" />
        <div className="flex flex-col h-full">
          <Input
            type="number"
            className="border-0 cursor-default bg-transparent focus-visible:ring-0 rounded-b-none rounded-t-xl border-b pt-12 pb-8 text-5xl text-right font-extralight [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
            value={display}
            readOnly
          />
          <div className="grid h-full grid-cols-4 gap-px text-xl">
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={handleClear}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/10">
                C
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={handleSignChange}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/10">
                ±
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={handlePercentage}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/10">
                %
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleOperatorClick("÷")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/20">
                ÷
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("7")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                7
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("8")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                8
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("9")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                9
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleOperatorClick("x")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/20">
                x
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("4")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                4
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("5")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                5
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("6")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                6
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleOperatorClick("-")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/20">
                -
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("1")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                1
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("2")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                2
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick("3")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                3
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleOperatorClick("+")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/20">
                +
              </div>
            </div>
            <div
              className="col-span-2 hover:bg-foreground/5"
              onClick={() => handleNumberClick("0")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                0
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={() => handleNumberClick(".")}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/5">
                ,
              </div>
            </div>
            <div
              className="cursor-pointer hover:bg-foreground/5"
              onClick={handleEqual}
            >
              <div className="flex items-center justify-center w-full h-full bg-foreground/20">
                =
              </div>
            </div>
          </div>
        </div>
      </Window>
    </>
  );
}

export default Main;
