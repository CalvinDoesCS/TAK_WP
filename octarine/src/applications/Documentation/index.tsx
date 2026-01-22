import { MemoryRouter } from "react-router-dom";
import { Window, ControlButtons } from "@/components/Window";
import Router from "./router";

function Main() {
  return (
    <>
      <Window
        x="center"
        y="center"
        width="70%"
        height="80%"
        maxWidth="90%"
        maxHeight="90%"
      >
        <ControlButtons className="mt-5" />
        <MemoryRouter>
          <Router />
        </MemoryRouter>
      </Window>
    </>
  );
}

export default Main;
