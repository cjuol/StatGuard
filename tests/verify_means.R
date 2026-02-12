#!/usr/bin/env Rscript

suppressPackageStartupMessages({
  library(MASS)
  library(DescTools)
})

x <- c(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000)

trimmed <- mean(x, trim = 0.1)
ql <- quantile(x, probs = 0.1, type = 7, names = FALSE)
qu <- quantile(x, probs = 0.9, type = 7, names = FALSE)
winsorized <- mean(pmin(pmax(x, ql), qu))
huber_mu <- MASS::huber(x)$mu

huber_k <- as.numeric(formals(MASS::huber)$k)
huber_tol <- as.numeric(formals(MASS::huber)$tol)

json <- sprintf(
  '{"trimmed":%.17g,"winsorized":%.17g,"huber":%.17g,"huber_k":%.17g,"huber_tol":%.17g}',
  trimmed,
  winsorized,
  huber_mu,
  huber_k,
  huber_tol
)

cat(json)
