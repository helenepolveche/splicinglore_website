FROM python:3.10-slim

LABEL maintainer="Nicolas Fontrodona <nicolas.fontrodona@ens-lyon.fr>"


RUN pip install pandas==1.4.2 \
    && pip install loguru==0.6.0 \
    && pip install lazyparser \
    && pip install statsmodels==0.13.2 \
    && pip install rich


COPY src /script/src
COPY data /script/data

ENV PYTHONPATH="/"

RUN useradd sf_user \
    && chown -R sf_user /script

USER sf_user

CMD ["bash"]