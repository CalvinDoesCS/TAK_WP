import { Button } from "@/components/Base/Button";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";

export interface MainProps extends React.ComponentPropsWithoutRef<"div"> {
  fileName: string;
  confirm: () => void;
}

function Main({ fileName, confirm }: MainProps) {
  return (
    <Window
      x="center"
      y="10%"
      width={400}
      height={155}
      minHeight={0}
      maxWidth={500}
      maxHeight={175}
    >
      <ControlButtons className="mt-2.5 ml-3" />
      <DraggableHandle className="absolute w-full h-20" />
      <div className="p-4 mt-5">
        The name{" "}
        <span className="font-medium text-foreground">"{fileName}"</span> is
        already taken. Please choose a different name.
        <div className="flex justify-end gap-2.5 mt-5">
          <Button size="sm" onClick={() => confirm()}>
            OK
          </Button>
        </div>
      </div>
    </Window>
  );
}

export default Main;
