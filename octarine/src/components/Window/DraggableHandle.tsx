import { cn } from "@/lib/utils";

export interface MainProps extends React.ButtonHTMLAttributes<HTMLDivElement> {}

function Main({ className, children }: MainProps) {
  return (
    <>
      <div className={cn("draggable-handle cursor-move", className)}>
        {children}
      </div>
    </>
  );
}

export default Main;
