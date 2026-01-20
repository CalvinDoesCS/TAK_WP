interface MainProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.HTMLAttributes<HTMLSpanElement>,
      HTMLSpanElement
    >
  > {}

function Main({ children, ...props }: MainProps) {
  return (
    <span
      {...props}
      className="px-1.5 py-0.5 text-xs border rounded bg-muted text-foreground"
    >
      {children}
    </span>
  );
}

export default Main;
