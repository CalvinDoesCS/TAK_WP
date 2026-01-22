interface MainProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.AnchorHTMLAttributes<HTMLAnchorElement>,
      HTMLAnchorElement
    >
  > {}

function Main({ children, ...props }: MainProps) {
  return (
    <a
      {...props}
      className="underline text-foreground decoration-dotted decoration-foreground/50 underline-offset-2"
    >
      {children}
    </a>
  );
}

export default Main;
