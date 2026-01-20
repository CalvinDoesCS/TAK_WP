import { Button } from "@/components/Base/Button";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";

export interface MainProps extends React.ComponentPropsWithoutRef<"div"> {
  actionType: "copy" | "cut";
  fileName: string;
  confirm: (userAction: "keep-both" | "stop" | "replace") => void;
}

function Main({ actionType, fileName, confirm }: MainProps) {
  return (
    <Window
      x="center"
      y="10%"
      width={400}
      height={175}
      minHeight={0}
      maxWidth={500}
      maxHeight={175}
    >
      <ControlButtons className="mt-2.5 ml-3" />
      <DraggableHandle className="absolute w-full h-20" />
      <div className="p-4 mt-5">
        An item named{" "}
        <span className="font-medium text-foreground">"{fileName}"</span>{" "}
        already exists in this location. Do you want to replace it with the one
        you're {actionType == "copy" ? "copying" : "moving"}?
        <div className="flex justify-end gap-2.5 mt-5">
          <Button size="sm" onClick={() => confirm("keep-both")}>
            Keep Both
          </Button>
          <Button size="sm" onClick={() => confirm("stop")}>
            Stop
          </Button>
          <Button size="sm" onClick={() => confirm("replace")}>
            Replace
          </Button>
        </div>
      </div>
    </Window>
  );
}

export default Main;
